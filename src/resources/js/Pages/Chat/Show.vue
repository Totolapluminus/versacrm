<script setup>
import {ref, nextTick, watch, onMounted, onUnmounted} from 'vue'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {usePage} from "@inertiajs/vue3";
import axios from "axios";
import {Link} from "@inertiajs/vue3";
import {useNotificationStore} from '@/Stores/notificationStore'

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


</script>


<template>
    <AuthenticatedLayout>
        <div class="grid grid-cols-12 p-4 gap-4 min-h-[calc(100vh-5rem)]">
            <!-- слева список чатов (пока заглушка) -->
            <aside class="col-span-3 bg-white rounded-2xl p-3 shadow-sm">
                <div v-for="bot in bots" :key="bot.id" class="space-y-2">
                    <h2>Бот "{{ bot.username }}"</h2>
                    <div v-for="chat in bot.telegram_chats">
                        <div v-if="user.id === chat.user_id" :class="[
                        'flex justify-between items-center p-3 rounded-xl my-2',
                        chat.id === props.current_chat?.id ? 'bg-blue-400 text-white' : 'bg-blue-200'
                        ]">
                            <Link :href="route('chat.show', chat.id)">
                                Чат с {{ chat.telegram_user?.username || chat.telegram_user?.first_name }}
                            </Link>
                            <span v-if="chat.status === 'open' && chat.id !== props.current_chat?.id" class="text-green-400">Новое!</span>
                            <span
                                v-if="chat.has_new"
                                class="w-3 h-3 bg-red-500 rounded-full"
                            ></span>
                        </div>
                        <div v-else :class="[
                        'flex justify-between p-3 rounded-xl my-2',
                        chat.id === props.current_chat?.id ? 'bg-blue-400 text-white' : 'bg-slate-100'
                        ]">
                            <Link :href="route('chat.show', chat.id)">
                                Чат с {{ chat.telegram_user?.username || chat.telegram_user?.first_name }}
                            </Link>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- лента сообщений -->
            <section
                class="col-span-7 bg-white rounded-2xl shadow-sm
            flex flex-col
            max-h-[calc(100vh-6.5rem)]
            overflow-y-auto"
            >
                <!-- скроллируется только этот div -->
                <div
                    ref="scrollEl"
                    class="flex-1 overflow-y-auto p-4 bg-slate-50 rounded-t-2xl space-y-2"
                >
                    <div
                        v-for="m in messages"
                        :key="m.id"
                        class="flex"
                        :class="m.direction === 'out' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[70%] px-3 py-2 rounded-2xl shadow-sm whitespace-pre-wrap break-words"
                            :class="m.direction === 'out'
                    ? 'bg-blue-600 text-white rounded-br-md'
                    : 'bg-white text-slate-900 rounded-bl-md'"
                        >
                            <p>{{ m.text }}</p>
                            <div class="mt-1 text-[11px] opacity-70 text-right">{{ m.time }}</div>
                        </div>
                    </div>
                </div>

                <form
                    @submit.prevent="send"
                    class="p-3 flex gap-2 rounded-b-2xl border-t bg-white"
                >
                    <input
                        v-model="draft"
                        type="text"
                        placeholder="Напишите сообщение…"
                        class="flex-1 px-4 py-2 rounded-xl border focus:outline-none focus:ring focus:ring-blue-200"
                    />
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700"
                    >
                        Отправить
                    </button>
                </form>
            </section>

            <aside class="col-span-2 bg-white rounded-2xl shadow-sm p-4 flex flex-col gap-4">
                <h3 class="font-semibold text-slate-700 mb-2">Опции чата</h3>
                <div>
                    <label for="status" class="text-sm text-slate-600 mb-1 block">Статус:</label>
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
                    <label for="operator" class="text-sm text-slate-600 mb-1 block">Оператор:</label>

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
            </aside>


        </div>
    </AuthenticatedLayout>
</template>

