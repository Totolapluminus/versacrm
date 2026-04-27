<script setup>
import {ref, nextTick, watch, onMounted, onUnmounted, computed, toRaw} from 'vue'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {router, usePage} from "@inertiajs/vue3";
import axios from "axios";
import {useNotificationStore} from '@/Stores/notificationStore'
import {useChatLabels} from "@/Composables/useChatLabels.js"
import ChatSidebar from "@/Components/Chat/ChatSidebar.vue";
import ChatOptionsPanel from "@/Components/Chat/ChatOptionsPanel.vue";
import MessageBubble from "@/Components/Chat/MessageBubble.vue";
import MessageForm from "@/Components/Chat/MessageForm.vue";
import {useChatListWebsocket} from "@/Composables/useChatListWebsocket.js";
import {useChatSend} from "@/Composables/useChatSend.js";

const { ticketTypeLabel, ticketDomainLabel } = useChatLabels()
const {props} = usePage()
const user = props.user ?? 'none'
const operators = props.operators ?? []
const currentChat = ref(props.current_chat ?? [])
const currentChatDbId = props.current_chat.id ?? []
const currentChatTgId = props.current_chat.chat_id ?? []
const messages = ref(props.current_chat.telegram_messages ?? [])
const chatStatus = ref(props.current_chat.status ?? 'none')
const chatOperator = ref(props.current_chat.user_id ?? 'none')
const botsRef = ref(structuredClone(toRaw(props.bots)))
const { fileInput, pickedFiles, draft, onPickFiles, openFilePicker, onPaste, removeFile, send } = useChatSend(
    messages,
    currentChatDbId,
    currentChatTgId,
    () => {
        chatStatus.value = 'in_progress'
    }
)
useChatListWebsocket(botsRef, currentChatDbId, (chat) => {
    if (chat.status) {
        chatStatus.value = chat.status
    }
})

const setFileInput = (el) => {
    fileInput.value = el
}
const scrollEl = ref(null)
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
const quickReplies = [
    'Добрый день, техподдержка МелГУ, пожалуйста опишите подробнее вашу проблему'
]
async function sendQuickReply(text) {
    await send(text)
}

const isRightPanelOpen = ref(false)
const isMobile = ref(false)
function checkMobile() {
    isMobile.value = window.innerWidth < 1024
    if (!isMobile.value) {
        isRightPanelOpen.value = false
    }
}
function toggleRightPanel() {
    isRightPanelOpen.value = !isRightPanelOpen.value
}
onMounted(() => {
    checkMobile()
    window.addEventListener('resize', checkMobile)
})
onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
})

const scrollToBottom = () => {
    if (!scrollEl.value) return
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
}
watch(
    () => messages.value.length,
    async () => {
        await nextTick()
        scrollToBottom()
    }
)
onMounted(() => scrollToBottom())

onMounted(() => {
    window.Echo.private(`store-telegram-message-to-chat-${currentChatDbId}`)
        .listen('.store-telegram-message-to-chat', res => {
            const msg = res.telegramMessage?.telegramMessage ?? res.telegramMessage
            messages.value.push(msg)

        })
})
onUnmounted(() => window.Echo.leave(`store-telegram-message-to-chat-${currentChatDbId}`))

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
        const chat = (bot.telegram_chats ?? []).find(c => c.id === chatId)

        if (chat) {
            chat.status = newStatus
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

</script>


<template>
    <AuthenticatedLayout>
        <div class="bg-white lg:grid lg:grid-cols-12 min-h-[calc(100vh-64px)] relative">

            <ChatSidebar :botsRef="botsRef" :currentChat="currentChat">

            </ChatSidebar>

            <!-- лента сообщений -->
            <section class="lg:col-span-7 shadow-sm flex flex-col h-[calc(100vh-4rem)] overflow-hidden min-w-0 pt-2 lg:pt-0">
                <div class="min-h-16 flex items-center justify-between bg-gray-50 border-b border-gray-100 px-3 sm:px-6 py-2">
                    <div class="flex flex-col gap-0.5 min-w-0">
                        <div class="text-sm sm:text-md font-bold truncate">
                            {{ currentChat.telegram_user?.username || currentChat.telegram_user?.first_name }},
                            {{ currentChat.ticket_id }}
                        </div>
                        <div class="text-[11px] sm:text-[13px] text-gray-500 leading-tight">
                            {{ lastMessageTime }},
                            {{ ticketDomainLabel(currentChat.ticket_domain) }} |
                            {{ ticketTypeLabel(currentChat.ticket_type) }} | Создан:
                            {{ currentChat.created_at_formatted }}
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="toggleRightPanel"
                        class="lg:hidden ml-3 shrink-0 px-3 py-2 rounded-lg border border-gray-200 text-xs font-medium bg-white hover:bg-gray-50"
                    >
                        Опции
                    </button>
                </div>
                <!-- скроллируется только этот div -->
                <div
                    ref="scrollEl"
                    class="flex-1 overflow-y-auto p-2 sm:p-4 space-y-1"
                >
                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="flex"
                        :class="message.direction === 'out' ? 'justify-end' : 'justify-start'"
                    >
                        <MessageBubble :message="message"></MessageBubble>
                    </div>
                </div>

                <MessageForm
                    :file-input-ref="setFileInput"
                    :draft="draft"
                    :picked-files="pickedFiles"
                    :is-disabled="chatStatus === 'closed' || (user.id !== currentChat.user_id && user.role !== 'admin')"
                    :textarea-placeholder="chatStatus === 'closed' || (user.id !== currentChat.user_id && user.role !== 'admin') ? 'ЗАЯВКА ЗАКРЫТА ИЛИ ПРИНАДЛЕЖИТ ДРУГОМУ ОПЕРАТОРУ' : 'Сообщение…'"
                    @update:draft="draft = $event"
                    @open-file-picker="openFilePicker"
                    @pick-files="onPickFiles"
                    @remove-file="removeFile"
                    @send="send"
                    @paste="onPaste"
                ></MessageForm>
            </section>

            <!-- desktop -->
            <aside class="hidden lg:block lg:col-span-2 shadow-xl relative z-10">
                <ChatOptionsPanel
                    :user="user"
                    :current-chat="currentChat"
                    :operators="operators"
                    :chat-status="chatStatus"
                    :chat-operator="chatOperator"
                    :quick-replies="quickReplies"
                    @update:chatStatus="chatStatus = $event"
                    @update:chatOperator="chatOperator = $event"
                    @update-status="updateStatus"
                    @update-operator="updateOperator"
                    @send-quick-reply="sendQuickReply"
                    @delete-chat="deleteChat"
                />
            </aside>

            <!-- mobile overlay -->
            <div
                v-if="isMobile && isRightPanelOpen"
                class="fixed inset-0 bg-black/40 z-40 lg:hidden"
                @click="isRightPanelOpen = false"
            >
            </div>

            <aside
                class="fixed top-0 right-0 h-full w-[85%] max-w-[340px] bg-white shadow-2xl z-50 transform transition-transform duration-300 lg:hidden"
                :class="isRightPanelOpen ? 'translate-x-0' : 'translate-x-full'"
            >
                <div class="flex items-center justify-between px-4 py-4 border-b">
                    <h3 class="font-semibold text-gray-700">Опции чата</h3>
                    <button type="button" @click="isRightPanelOpen = false" class="px-2 py-1 text-sm rounded-md border border-gray-200">
                        Закрыть
                    </button>
                </div>

                <ChatOptionsPanel
                    :user="user"
                    :current-chat="currentChat"
                    :operators="operators"
                    :chat-status="chatStatus"
                    :chat-operator="chatOperator"
                    :quick-replies="quickReplies"
                    :compact="true"
                    @update:chatStatus="chatStatus = $event"
                    @update:chatOperator="chatOperator = $event"
                    @update-status="updateStatus"
                    @update-operator="updateOperator"
                    @send-quick-reply="sendQuickReply"
                    @delete-chat="deleteChat"
                />
            </aside>


        </div>
    </AuthenticatedLayout>
</template>

