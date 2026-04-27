import {onMounted, onUnmounted} from "vue";

export function useChatListWebsocket(botsRef, currentChatDbId = null, onCurrentChatStatusChange = null){
    onMounted(() => {
        window.Echo.channel('store-telegram-chat')
            .listen('.store-telegram-chat', res => {
                const chat = res.telegramChat
                if (!chat) return
                const bot = botsRef.value.find(b => b.id === chat.telegram_bot_id)
                if (!bot) return

                bot.telegram_chats = bot.telegram_chats ?? []
                const found = bot.telegram_chats.find(c => c.id === chat.id)

                if (chat.id === currentChatDbId && typeof onCurrentChatStatusChange === 'function') {
                    onCurrentChatStatusChange(chat)
                }

                if (found) {
                    found.has_new = chat.has_new
                    found.status = chat.status
                    found.last_message_in_human = chat.last_message_in_human
                    found.last_message = chat.last_message
                    return
                }

                bot.telegram_chats.unshift(chat)

            })
    })
    onUnmounted(() => window.Echo.leave(`store-telegram-chat`))
}
