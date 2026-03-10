<script setup>
import {ref, nextTick, watch, onMounted, onUnmounted, computed, toRaw} from 'vue'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {router, usePage} from "@inertiajs/vue3";
import axios from "axios";
import {Link} from "@inertiajs/vue3";
import {useNotificationStore} from '@/Stores/notificationStore'

import {PaperAirplaneIcon, PaperClipIcon, XCircleIcon, XMarkIcon} from "@heroicons/vue/24/solid/index.js";
import Attachment from "@/Components/Chat/Attachment.vue";

const {props} = usePage()
const user = props.user ?? 'none'
const operators = props.operators ?? []
const bots = ref(props.bots ?? [])
const currentChat = ref(props.current_chat ?? [])
const currentChatDbId = props.current_chat.id ?? []
const currentChatTgId = props.current_chat.chat_id ?? []
const messages = ref(props.current_chat.telegram_messages ?? [])

const chatStatus = ref(props.current_chat.status ?? 'none')
const chatOperator = ref(props.current_chat.user_id ?? 'none')

const fileInput = ref(null)
const pickedFiles = ref([])
const draft = ref('')
const scrollEl = ref(null)

const botsRef = ref(structuredClone(toRaw(props.bots)))
const closedChats = computed(() =>
    botsRef.value.flatMap(b => b.closed_chats ?? [])
)

const lastMessage = computed(() => {
    if (!messages.value.length) return null
    return messages.value[messages.value.length - 1]
})

const lastMessageTime = computed(() => {
    return lastMessage.value?.time_human ?? 'Нет сообщений'
})

const notificationStore = useNotificationStore()

onMounted(() => {
    if (user.role !== 'admin') notificationStore.clearChat(props.current_chat.id)
})

function onPickFiles(event) {
    const files = event.target.files ? Array.from(event.target.files) : []
    pickedFiles.value = files
}

function openFilePicker() {
    fileInput.value.click()
}

function onPaste(e) {
    const items = e.clipboardData?.items
    if (!items) return

    for (const item of items) {
        if (item.type && item.type.startsWith('image/')) {
            const blob = item.getAsFile()
            if (!blob) continue

            // превращаем в File (чтобы имя было нормальное)
            const ext = blob.type.split('/')[1] || 'png'
            const file = new File([blob], `paste-${Date.now()}.${ext}`, {type: blob.type})

            pickedFiles.value.push(file)

            // чтобы не вставлялся "пустой символ" в input
            e.preventDefault()
            break
        }
    }
}
function removeFile(index) {
    if (index < 0 || index >= pickedFiles.value.length) return

    pickedFiles.value.splice(index, 1)

    // если удалили всё — сбросить input, чтобы можно было снова выбрать те же файлы
    if (pickedFiles.value.length === 0 && fileInput.value) {
        fileInput.value.value = ''
    }
}

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

            console.log(res)
            console.log(messages.value)
            console.log('A1', res.telegramMessage?.attachments_urls)
            console.log('A2', res.telegramMessage?.telegramMessage?.attachments_urls)

            const msg = res.telegramMessage?.telegramMessage ?? res.telegramMessage
            messages.value.push(msg)
        })
})
onMounted(() => {
    window.Echo.channel('store-telegram-chat')
        .listen('.store-telegram-chat', res => {
            const chat = res.telegramChat
            const bot = botsRef.value.find(b => b.id === chat.telegram_bot_id)
            if (!bot) return

            bot.telegram_chats = bot.telegram_chats ?? []
            bot.closed_chats = bot.closed_chats ?? []

            const open = bot.telegram_chats
            const closed = bot.closed_chats

            const openIdx = open.findIndex(c => c.id === chat.id)
            const closedIdx = closed.findIndex(c => c.id === chat.id)

            let target = null

            if (openIdx !== -1) {
                target = open[openIdx]
            }
            if (!target && closedIdx !== -1) {
                target = closed[closedIdx]
            }

            if (target && chat.status && target.status !== chat.status) {
                moveChatStatus(chat.id, chat.status)

                const newOpenIdx = open.findIndex(c => c.id === chat.id)
                const newClosedIdx = closed.findIndex(c => c.id === chat.id)
                target = newOpenIdx !== -1 ? open[newOpenIdx] : (newClosedIdx !== -1 ? closed[newClosedIdx] : target)
            }

            if (chat.id === currentChatDbId && chat.status) {
                chatStatus.value = chat.status
            }

            if (target) {
                Object.assign(target, chat)
                return
            }

            if (chat.status === 'closed') {
                closed.unshift(chat)
            } else {
                open.unshift(chat)
            }
        })
})
onUnmounted(() => window.Echo.leave(`store-telegram-message-to-chat-${currentChatDbId}`))
onUnmounted(() => window.Echo.leave(`store-telegram-chat`))


async function send() {

    const text = draft.value.trim()
    const files = pickedFiles.value

    if (!text && files.length === 0) return

    // отображение сообщения на фронте заранее (временно)
    const tmpId = `tmp-${Date.now()}`
    const optimistic = {
        id: tmpId,
        direction: 'out',
        text: text || null,
        telegram_chat_db_id: currentChatDbId,
        telegram_chat_tg_id: currentChatTgId,
        attachments_urls: files.length ? files.map(f => URL.createObjectURL(f)) : [],
    }

    messages.value.push(optimistic)

    draft.value = ''
    pickedFiles.value = []
    if (fileInput.value) fileInput.value.value = ''

    //отправка данных на бекенд
    try {
        const formData = new FormData()
        formData.append('direction', 'out')
        if (text) formData.append('text', text)
        formData.append('telegram_chat_db_id', currentChatDbId)
        formData.append('telegram_chat_tg_id', currentChatTgId)
        for (const file of files) {
            formData.append('attachments[]', file)
        }


        const response = await axios.post(`/api/chat/${currentChatDbId}`, formData)

        const idx = messages.value.findIndex(m => m.id === tmpId)
        if (idx !== -1) {
            // ОЧИСТКА ПАМЯТИ
            for (const url of (messages.value[idx].attachments_urls || [])) {
                if (typeof url === 'string' && url.startsWith('blob:')) URL.revokeObjectURL(url)
            }
            messages.value[idx] = response.data
        }

        chatStatus.value = 'in_progress'

    } catch (e) {
        //откат, если ошибка
        const idx = messages.value.findIndex(m => m.id === tmpId)
        if (idx !== -1) {
            //ОЧИСТКА ПАМЯТИ
            for (const url of (messages.value[idx].attachments_urls || [])) {
                if (typeof url === 'string' && url.startsWith('blob:')) URL.revokeObjectURL(url)
            }
            messages.value.splice(idx, 1)
        }
        console.error('Не удалось отправить сообщение', e)
    }
}

async function updateOperator() {
    try {
        await axios.put(`/api/chat/${currentChatDbId}/operator`, {
            user_id: chatOperator.value,
        })
        router.get(route('chat.index'))
    } catch (e) {
        console.error("Ошибка смены оператора", e)
    }
}

function moveChatStatus(chatId, newStatus) {
    for (const bot of botsRef.value) {
        const open = bot.telegram_chats ?? []
        const closed = bot.closed_chats ?? (bot.closed_chats = [])

        // если чат был в открытых
        let idx = open.findIndex(c => c.id === chatId)
        if (idx !== -1) {
            const chat = open.splice(idx, 1)[0]
            chat.status = newStatus
            if (newStatus === 'closed') closed.unshift(chat)
            else open.unshift(chat)
            return
        }

        // если чат был в закрытых
        idx = closed.findIndex(c => c.id === chatId)
        if (idx !== -1) {
            const chat = closed.splice(idx, 1)[0]
            chat.status = newStatus
            if (newStatus === 'closed') closed.unshift(chat)
            else open.unshift(chat)
            return
        }
    }
}

async function updateStatus() {
    try {
        await axios.put(`/api/chat/${currentChatDbId}/status`, {status: chatStatus.value})
        moveChatStatus(currentChatDbId, chatStatus.value)
    } catch (e) {
        console.error('Ошибка при обновлении статуса', e)
    }
}

async function deleteChat() {
    if (!confirm('Удалить чат и все сообщения?')) return

    try {
        await axios.delete(`/api/chat/${currentChatDbId}`)
        router.get(route('chat.index'))
    } catch (e) {
        console.error('Ошибка при удалении чата', e)
    }
}

const getInitials = (chat) => {
    const name = chat.telegram_user?.username || chat.telegram_user?.first_name || ''
    const trimmed = name.trim()

    if (!trimmed) return '?'

    const letters = trimmed.replace(/[^A-Za-zА-Яа-яЁё0-9]/g, '')

    return letters.slice(0, 1).toUpperCase()
}

const TICKET_TYPE_LABELS = {
    login: 'Проблемы со входом',
    access: 'Проблемы с разделами',
    profile_update: 'Проблемы с профилем',
    notifications: 'Проблемы с уведомлениями',
    bug: 'Баг в кабинете',
    other: 'Другие проблемы',
}
const ticketTypeLabel = (key) => {
    if (!key) return '—'
    return TICKET_TYPE_LABELS[key] || key
}
const TICKET_DOMAIN_LABELS = {
    my: 'my.melsu',
    lms: 'lms.melsu',
    rasp: 'rasp.melsu',
    other: 'Другое',
}
const ticketDomainLabel = (key) => {
    if (!key) return '—'
    return TICKET_DOMAIN_LABELS[key] || key
}

</script>


<template>
    <AuthenticatedLayout>
        <div class="bg-white grid grid-cols-12 min-h-[calc(100vh-64px)]">
            <!-- слева список чатов (пока заглушка) -->
            <aside class="col-span-3 bg-white shadow-lg relative z-10 max-h-[calc(100vh-4rem)] overflow-y-auto">
                <div v-for="bot in botsRef" :key="bot.id">
                    <h2 class="bg-gray-50 shadow-sm py-1.5 pl-6">Бот "{{ bot.username }}"</h2>
                    <div class="my-3">
                        <div v-for="chat in bot.telegram_chats">
                            <Link v-if="user.id === chat.user_id || user.role === 'admin'"
                                  :href="route('chat.show', chat.id)" :class="[
                            'flex items-center gap-3 px-4 py-2',
                            chat.id === props.current_chat?.id ? 'bg-gray-50' : 'bg-white hover:bg-gray-50 transition duration-50'
                            ]">

                                <div
                                    class="flex items-center justify-center h-10 w-10 rounded-full bg-red-700 text-gray-100 font-semibold text-2xl">
                                    {{ getInitials(chat) }}
                                </div>

                                <div class="flex flex-1 flex-col gap-2 truncate">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[13px] font-bold text-gray-800 truncate">{{
                                                chat.telegram_user?.username || chat.telegram_user?.first_name
                                            }}, {{ chat.ticket_id }}</span>
                                        <span class="text-[11px] text-gray-400">{{ chat.last_message_in_human }}</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-[13px] text-gray-400 truncate">{{
                                                chat.last_message_in_text
                                            }}</span>
                                        <div class="flex items-center gap-1.5">
                                            <div class="flex items-center gap-0.5">
                                                <span class="text-[10px] text-gray-400">{{
                                                        ticketDomainLabel(chat.ticket_domain)
                                                    }}</span>
                                                <span class="text-[10px] text-gray-400">|</span>
                                                <span class="text-[10px] text-gray-400">{{
                                                        ticketTypeLabel(chat.ticket_type)
                                                    }}</span>
                                            </div>
                                            <div class="flex items-center gap-0.5">
                                                <div v-if="chat.status === 'open'"
                                                     class="w-2 h-2 bg-blue-700 rounded-full"></div>
                                                <div v-if="chat.has_new" class="w-2 h-2 bg-red-700 rounded-full"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
                <h2 class="bg-gray-50 shadow-sm py-1.5 pl-6">Закрытые обращения</h2>
                <div v-if="closedChats.length" class="my-3">
                    <div v-for="chat in closedChats" :key="'closed-' + chat.id" class="px-6 py-1">
                        <Link :href="route('chat.show', chat.id)"
                              class="flex justify-between text-sm text-gray-600 hover:text-gray-900">
                            <div>{{ chat.telegram_user?.username || chat.telegram_user?.first_name }}</div>
                            <div>{{ chat.last_message_in_human }}</div>
                        </Link>
                    </div>
                </div>

                <div v-else class="px-6 py-2 text-sm text-gray-400">
                    Нет закрытых обращений
                </div>
            </aside>

            <!-- лента сообщений -->
            <section
                class="col-span-7 shadow-sm
            flex flex-col
            max-h-[calc(100vh-4rem)]
            overflow-y-auto"
            >
                <div class="h-16 flex items-center bg-gray-50 border-b border-gray-100">
                    <div class="flex flex-col gap-0.5 px-6">
                        <div class="text-md font-bold">
                            {{ currentChat.telegram_user?.username || currentChat.telegram_user?.first_name }},
                            {{ currentChat.ticket_id }}
                        </div>
                        <div class="text-[13px] text-gray-500">{{ lastMessageTime }},
                            {{ ticketDomainLabel(currentChat.ticket_domain) }} |
                            {{ ticketTypeLabel(currentChat.ticket_type) }} | Создан:
                            {{ currentChat.created_at_formatted }}
                        </div>
                    </div>
                </div>
                <!-- скроллируется только этот div -->
                <div
                    ref="scrollEl"
                    class="flex-1 overflow-y-auto p-4 space-y-1"
                >
                    <div
                        v-for="m in messages"
                        :key="m.id"
                        class="flex"
                        :class="m.direction === 'out' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[70%] min-w-[calc(4rem)] px-2 py-1.5 rounded-2xl shadow-md text-sm whitespace-pre-wrap break-words"
                            :class="m.direction === 'out'
                    ? 'bg-red-700 border border-red-600 text-white rounded-br-md'
                    : 'bg-gray-50 border border-gray-50 text-gray-900 rounded-bl-md'"
                        >
                            <div v-if="m.attachments_urls?.length" class="grid gap-2 justify-items-center"
                                 :class="m.attachments_urls.length === 1 ? 'grid-cols-1' : 'grid-cols-2'">
                                <Attachment v-for="attachment_url in m.attachments_urls"
                                            :src="attachment_url"></Attachment>
                            </div>

                            <p v-if="m.text">{{ m.text }}</p>
                            <div class="mt-1 opacity-70 text-[11px] text-right">{{ m.time_human }}</div>
                        </div>
                    </div>
                </div>

                <form
                    @submit.prevent="send"
                    class="p-3 flex gap-2 border-t items-center max-h-[200px]"
                >
                    <textarea
                        v-model="draft"
                        @paste="onPaste"
                        rows="1"
                        type="text"
                        :disabled="chatStatus === 'closed' || (user.id !== currentChat.user_id && user.role !== 'admin')"
                        :placeholder="chatStatus === 'closed' || (user.id !== currentChat.user_id && user.role !== 'admin') ? 'ЗАЯВКА ЗАКРЫТА ИЛИ ПРИНАДЛЕЖИТ ДРУГОМУ ОПЕРАТОРУ' : 'Сообщение…'"
                        class="flex-1 px-4 py-2 rounded-xl border-none focus:outline-none focus:ring-0 focus:ring-blue-200 text-sm resize-none"
                    />
                    <span v-if="pickedFiles.length" class="text-xs text-gray-500 max-w-[250px]">
                        <div v-for="(file, i) in pickedFiles"
                             :key="file.name + file.size + i"
                             @click="removeFile(i)" class="cursor-pointer relative group"
                        >
                            <div class="truncate">{{ file.name }}</div>
                            <XCircleIcon class="hidden absolute rounded-full size-5 text-red-400 -top-1.5 -right-2.5 group-hover:block"></XCircleIcon>
                        </div>
                    </span>
                    <input
                        ref="fileInput"
                        type="file"
                        multiple
                        accept="image/*"
                        @change="onPickFiles"
                        :disabled="chatStatus === 'closed' || (user.id !== currentChat.user_id && user.role !== 'admin')"
                        class="hidden"
                    />
                    <button
                        type="button"
                        @click="openFilePicker"
                        class="w-15 h-10 px-4 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition"
                        :disabled="chatStatus === 'closed' || (user.id !== currentChat.user_id && user.role !== 'admin')"
                    >
                        <PaperClipIcon class="size-5 text-gray-700"/>
                    </button>
                    <button
                        type="submit"
                        class="w-15 h-10 px-4 py-2 rounded-xl bg-red-700 text-white hover:bg-red-800 transition duration-50"
                        :disabled="chatStatus === 'closed' || (user.id !== currentChat.user_id && user.role !== 'admin')"
                    >
                        <PaperAirplaneIcon class="text-white size-5"></PaperAirplaneIcon>
                    </button>
                </form>
            </section>

            <aside class="col-span-2 shadow-xl relative z-10">
                <div v-if="user.id === currentChat.user_id || user.role === 'admin'"
                     class="bg-white rounded-2xl shadow-md p-4 m-4 border border-gray-100 flex flex-col gap-4">
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
                    <button
                        v-if="chatStatus === 'closed'"
                        type="button"
                        @click="deleteChat"
                        class="w-full bg-red-700 text-white rounded-lg px-3 py-2 text-sm font-semibold hover:bg-red-800 focus:outline-none focus:ring focus:ring-red-200"
                    >
                        Удалить чат
                    </button>
                </div>
                <div v-else class="px-3 py-6 text-center font-bold text-2xl">ЧАТ ПРИНАДЛЕЖИТ ДРУГОМУ ОПЕРАТОРУ<br><br>
                    ОПЦИИ НЕДОСТПНЫ
                </div>
            </aside>


        </div>
    </AuthenticatedLayout>
</template>

