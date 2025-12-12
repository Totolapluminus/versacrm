<script setup>
import {ref, nextTick, watch, onMounted, onUnmounted} from 'vue'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {usePage} from "@inertiajs/vue3";
import axios from "axios";
import {Link} from "@inertiajs/vue3";
import {useNotificationStore} from '@/Stores/notificationStore'

import telegramIcon from '@/Images/telegram.png'
console.log(telegramIcon)
const notificationStore = useNotificationStore()

const {props} = usePage()
const user = props.user ?? 'none'
const operators = props.operators ?? []
const bots = ref(props.bots ?? [])
const currentChatDbId = props.current_chat.id ?? []
const currentChatTgId = props.current_chat.chat_id ?? []
const messages = ref(props.current_chat.telegram_messages ?? [])

const chatStatus = ref(props.current_chat.status ?? 'none')
const chatOperator = ref(props.current_chat.user_id ?? 'none')

const draft = ref('')
const scrollEl = ref(null)


notificationStore.clearChat(currentChatDbId)


const scrollToBottom = () => {
    if (!scrollEl.value) return
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
}


watch(messages, async () => {
    await nextTick();
    scrollToBottom()
})

onMounted(() => scrollToBottom())

onMounted(() => {
    window.Echo.private(`store-telegram-message-to-chat-${currentChatDbId}`)
        .listen('.store-telegram-message-to-chat', res => {
            messages.value.push(res.telegramMessage)
            console.log(res.telegramMessage)
        })
})
onMounted(() => {
    window.Echo.channel('store-telegram-chat')
        .listen('.store-telegram-chat', res => {
            const chat = res.telegramChat
            console.log(chat)
            const bot = bots.value.find(b => b.id === chat.telegram_bot_id)
            if (!bot) return

            const found = bot.telegram_chats.find(c => c.id === chat.id)
            if (found) {
                found.has_new = chat.has_new
                console.log(found)
                return
            }
            bot.telegram_chats.push(chat)
        })
})
onUnmounted(() => window.Echo.leave(`store-telegram-message-to-chat-${currentChatDbId}`))
onUnmounted(() => window.Echo.leave(`store-telegram-chat`))


async function send() {

    const text = draft.value.trim()
    if (!text) return

    // отображение сообщения на фронте заранее (временно)
    const tmpId = `tmp-${Date.now()}`
    const optimistic = {
        id: tmpId,
        direction: 'out',
        text: text,
        telegram_chat_db_id: currentChatDbId,
        telegram_chat_tg_id: currentChatTgId,
    }

    messages.value.push(optimistic)
    draft.value = ''

    //отправка данных на бекенд
    try {
        const response = await axios.post(`/api/chat/${currentChatDbId}`, {
            direction: 'out',
            text: text,
            telegram_chat_db_id: currentChatDbId,
            telegram_chat_tg_id: currentChatTgId,
        })
        console.log(response)

        //замена временного сообщения серверным
        const idx = messages.value.findIndex(m => m.id === tmpId)
        if (idx !== -1) messages.value[idx] = response.data

        chatStatus.value = 'in_progress'

    } catch (e) {
        //откат, если ошибка
        const idx = messages.value.findIndex(m => m.id === tmpId)
        if (idx !== -1) messages.value.splice(idx, 1)
        console.error('Не удалось отправить сообщение', e)
    }
}

async function updateOperator() {
    try {
        await axios.put(`/api/chat/${currentChatDbId}/operator`, {
            user_id: chatOperator.value,
        })
        console.log("Оператор обновлён:", chatOperator.value)
    } catch (e) {
        console.error("Ошибка смены оператора", e)
    }
}

async function updateStatus() {
    console.log(chatStatus.value)
    try {
        await axios.put(`/api/chat/${currentChatDbId}/status`, {
            status: chatStatus.value,
        })
        console.log('Статус обновлён:', chatStatus.value)
    } catch (e) {
        console.error('Ошибка при обновлении статуса', e)
    }
}

const getInitials = (chat) => {
    const name = chat.telegram_user?.username || chat.telegram_user?.first_name || ''
    const trimmed = name.trim()

    if (!trimmed) return '?'

    const letters = trimmed.replace(/[^A-Za-zА-Яа-яЁё0-9]/g, '')

    return letters.slice(0, 1).toUpperCase()
}

</script>


<template>
    <AuthenticatedLayout>
        <div class="bg-white grid grid-cols-12 min-h-[calc(100vh-64px)]">
            <!-- слева список чатов (пока заглушка) -->
            <aside class="col-span-3 bg-white shadow-xl">
                <div v-for="bot in bots" :key="bot.id">
                    <h2 class="border-y border-gray-100 shadow-md py-1.5 pl-6" >Бот "{{ bot.username }}"</h2>
                    <div class="my-3">
                        <div v-for="chat in bot.telegram_chats">
                            <Link v-if="user.id === chat.user_id" :href="route('chat.show', chat.id)" :class="[
                            'flex items-center gap-4 px-6 py-2',
                            chat.id === props.current_chat?.id ? 'bg-gray-100' : 'bg-white hover:bg-gray-100 transition duration-50'
                            ]">

                                <div class="flex items-center justify-center h-10 w-10 rounded-full bg-red-600 text-gray-100 font-semibold text-2xl">
                                    {{ getInitials(chat) }}
                                </div>

                                <div class="flex flex-1 flex-col gap-1">
                                    <div class="flex justify-between items-center">
                                        <span :class="['text-md font-bold', chat.id === props.current_chat?.id ? 'text-gray-800' : 'text-gray-800']"> {{ chat.telegram_user?.username || chat.telegram_user?.first_name }} </span>
                                        <span :class="['text-sm', chat.id === props.current_chat?.id ? 'text-gray-400' : 'text-gray-400']">14:50</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span :class="['text-sm', chat.id === props.current_chat?.id ? 'text-gray-400' : 'text-gray-400']" >Я понял!</span>
                                        <span v-if="chat.has_new" class="w-2 h-2 bg-red-500 rounded-full"></span>
                                        <span v-if="chat.status === 'open'" class="w-2 h-2 bg-orange-500 rounded-full"></span>
                                    </div>
                                </div>
                            </Link>
<!--                            <div v-else :class="[-->
<!--                        'flex justify-between p-3 rounded-xl my-2',-->
<!--                        chat.id === props.current_chat?.id ? 'bg-blue-400 text-white' : 'bg-gray-100'-->
<!--                        ]">-->
<!--                                <Link :href="route('chat.show', chat.id)">-->
<!--                                    Чат с {{ chat.telegram_user?.username || chat.telegram_user?.first_name }}-->
<!--                                </Link>-->
<!--                            </div>-->
                        </div>
                    </div>

                </div>
            </aside>

            <!-- лента сообщений -->
            <section
                class="col-span-7 shadow-sm
            flex flex-col
            max-h-[calc(100vh-4rem)]
            overflow-y-auto"
            >
                <!-- скроллируется только этот div -->
                <div
                    ref="scrollEl"
                    class="flex-1 overflow-y-auto p-4 space-y-2"
                >
                    <div
                        v-for="m in messages"
                        :key="m.id"
                        class="flex"
                        :class="m.direction === 'out' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[70%] min-w-[calc(4rem)] px-3 py-2 rounded-3xl shadow-md whitespace-pre-wrap break-words"
                            :class="m.direction === 'out'
                    ? 'bg-red-600 border border-red-600 text-white rounded-br-md'
                    : 'bg-white border border-gray-100 text-gray-900 rounded-bl-md'"
                        >
                            <p>{{ m.text }}</p>
<!--                            <div class="mt-1 text-[11px] opacity-70 text-right">{{ m.time }}</div>-->
                            <div class="mt-1 text-[11px] opacity-70 text-right">14:50</div>
                        </div>
                    </div>
                </div>

                <form
                    @submit.prevent="send"
                    class="p-3 flex gap-2 border-t"
                >
                    <input
                        v-model="draft"
                        type="text"
                        placeholder="Cообщение…"
                        class="flex-1 px-4 py-2 rounded-xl border-none focus:outline-none focus:ring-0 focus:ring-blue-200"
                    />
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-xl bg-red-600 text-white hover:bg-red-800 transition duration-50"
                    >
                        Отправить
                    </button>
                </form>
            </section>

            <aside class="col-span-2 shadow-xl">
                <div class="bg-white rounded-2xl shadow-md p-4 m-4 border border-gray-100 flex flex-col gap-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Опции чата</h3>
                    <div>
                        <label for="status" class="text-sm text-gray-600 mb-1 block">Статус:</label>
                        <select
                            id="status"
                            v-model="chatStatus"
                            @change="updateStatus"
                            class="w-full border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-blue-200"
                        >
                            <option value="open">Открыт</option>
                            <option value="in_progress">В работе</option>
                            <option value="closed">Закрыт</option>
                        </select>
                    </div>
                    <div>
                        <label for="operator" class="text-sm text-gray-600 mb-1 block">Оператор:</label>

                        <select
                            id="operator"
                            v-model="chatOperator"
                            @change="updateOperator"
                            class="w-full border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-blue-200"
                        >
                            <option disabled value="">Выберите оператора</option>
                            <option
                                v-for="operator in operators"
                                :key="operator.id"
                                :value="operator.id"
                            >
                                {{ operator.name }}
                            </option>
                        </select>
                    </div>
                </div>

            </aside>


        </div>
    </AuthenticatedLayout>
</template>

