<script setup>
import {ref, onMounted, onUnmounted, toRaw, computed} from 'vue'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {usePage} from "@inertiajs/vue3";
import {Link} from "@inertiajs/vue3";

const {props} = usePage()
const user = props.user ?? 'none'

const scrollEl = ref(null)
const isClosedChatsOpen = ref(false)

const botsRef = ref(structuredClone(toRaw(props.bots)))
const closedChats = computed(() =>
    botsRef.value.flatMap(bot => bot.telegram_chats ?? []).filter(chat => chat.status === 'closed')
)

const scrollToBottom = () => {
    if (!scrollEl.value) return
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
}

onMounted(() => scrollToBottom())

onMounted(() => {
    window.Echo.channel('store-telegram-chat')
        .listen('.store-telegram-chat', res => {
            const chat = res.telegramChat
            const bot = botsRef.value.find(b => b.id === chat.telegram_bot_id)
            if (!bot) return

            bot.telegram_chats = bot.telegram_chats ?? []

            const found = bot.telegram_chats.find(c => c.id === chat.id)

            if (found) {
                found.has_new = chat.has_new
                found.status = chat.status
                found.last_message = chat.last_message
                found.last_message_in_human = chat.last_message_in_human
                return
            }

            bot.telegram_chats.push(chat)
        })
})

onUnmounted(() => window.Echo.leave('store-telegram-chat'))

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
        <div class="bg-white lg:grid lg:grid-cols-12 min-h-[calc(100vh-64px)] relative">
            <!-- слева список чатов -->
            <aside class="lg:col-span-3 bg-white lg:shadow-lg relative z-10 max-h-[calc(100vh-4rem)] overflow-y-auto border-r border-gray-100">
                <div v-for="bot in botsRef" :key="bot.id">
                    <h2 class="bg-gray-50 shadow-sm py-1.5 pl-4 sm:pl-6 text-sm sm:text-base">
                        Бот "{{ bot.username }}"
                    </h2>

                    <div class="my-3">
                        <div
                            v-for="chat in bot.telegram_chats.filter(chat => chat.status !== 'closed')"
                            :key="chat.id"
                        >
                            <Link
                                v-if="user.id === chat.user_id || user.role === 'admin'"
                                :href="route('chat.show', chat.id)"
                                class="flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2 bg-white hover:bg-gray-50 transition duration-50"
                            >
                                <div
                                    class="flex items-center justify-center h-8 w-8 sm:h-10 sm:w-10 rounded-full bg-red-700 text-gray-100 font-semibold text-lg sm:text-2xl">
                                    {{ getInitials(chat) }}
                                </div>

                                <div class="flex flex-1 flex-col gap-2 truncate">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[12px] sm:text-[13px] font-bold text-gray-800 truncate">
                                            {{
                                                chat.telegram_user?.username || chat.telegram_user?.first_name
                                            }}, {{ chat.ticket_id }}
                                        </span>
                                        <span class="text-[10px] sm:text-[11px] text-gray-400">
                                            {{ chat.last_message_in_human }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-[11px] sm:text-[13px] text-gray-400 truncate">
                                            {{ chat.last_message?.text }}
                                        </span>

                                        <div class="flex items-center gap-1.5">
                                            <div class="flex items-center gap-0.5">
                                                <span class="text-[10px] text-gray-400">
                                                    {{ ticketDomainLabel(chat.ticket_domain) }}
                                                </span>
                                                <span class="text-[10px] text-gray-400">|</span>
                                                <span class="text-[10px] text-gray-400">
                                                    {{ ticketTypeLabel(chat.ticket_type) }}
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-0.5">
                                                <div
                                                    v-if="chat.status === 'open'"
                                                    class="w-2 h-2 bg-blue-700 rounded-full"
                                                ></div>
                                                <div
                                                    v-if="chat.has_new"
                                                    class="w-2 h-2 bg-red-700 rounded-full"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gray-50 shadow-sm py-1.5 px-4 sm:px-6 flex items-center justify-between cursor-pointer"
                    @click="isClosedChatsOpen = !isClosedChatsOpen"
                >
                    <h2 class="text-sm sm:text-base">Закрытые обращения</h2>
                    <button type="button" class="text-sm text-gray-500">
                        {{ isClosedChatsOpen ? '▾' : '▸' }}
                    </button>
                </div>

                <div v-if="isClosedChatsOpen">
                    <div v-if="closedChats.length" class="my-3">
                        <div v-for="chat in closedChats" :key="'closed-' + chat.id" class="px-6 py-1">
                            <Link
                                :href="route('chat.show', chat.id)"
                                class="flex justify-between text-sm text-gray-600 hover:text-gray-900"
                            >
                                <div>{{ chat.telegram_user?.username || chat.telegram_user?.first_name }}</div>
                                <div>{{ chat.last_message_in_human }}</div>
                            </Link>
                        </div>
                    </div>

                    <div v-else class="px-6 py-2 text-sm text-gray-400">
                        Нет закрытых обращений
                    </div>
                </div>
            </aside>

            <!-- правая часть -->
            <section ref="scrollEl" class="lg:col-span-9 flex flex-col min-w-0 pt-2 lg:pt-0">
                <div class="min-h-16 flex items-center bg-gray-50 border-b border-gray-100 px-3 sm:px-6 py-2">
                    <div class="flex flex-col gap-0.5 min-w-0">
                        <div class="text-sm sm:text-base font-bold truncate">
                            Список обращений
                        </div>
                        <div class="text-[11px] sm:text-[13px] text-gray-500 leading-tight">
                            Выберите чат слева, чтобы открыть переписку
                        </div>
                    </div>
                </div>

                <div
                    class="flex-1 overflow-y-auto p-4 sm:p-6 flex items-center justify-center text-center text-gray-400 text-sm sm:text-base">
                    Выберите обращение из списка слева
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
