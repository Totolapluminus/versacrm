<script setup>
import {ref, onMounted, onUnmounted, toRaw, computed} from 'vue'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {usePage} from "@inertiajs/vue3";
import {Link} from "@inertiajs/vue3";
import telegramIcon from "@/Images/telegram.png";


const {props} = usePage()
const user = props.user ?? 'none'
const bots = ref(props.bots ?? [])

const draft = ref('')
const scrollEl = ref(null)

const botsRef = ref(structuredClone(toRaw(props.bots)))
const closedChats = computed(() =>
    botsRef.value.flatMap(b => b.closed_chats ?? [])
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
            console.log(chat)
            const bot = botsRef.value.find(b => b.id === chat.telegram_bot_id)
            if (!bot) return

            const found = bot.telegram_chats.find(c => c.id === chat.id)
            if (found) {
                found.has_new = chat.has_new
                found.last_message_in_text = chat.last_message_in_text
                found.last_message_in_human = chat.last_message_in_human
                console.log(found)
                return
            }
            bot.telegram_chats.push(chat)
        })
})

onUnmounted(() => window.Echo.leave(`store-telegram-chat`))

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
    return TICKET_TYPE_LABELS[key] || key // если ключ неизвестен — покажем его
}

</script>


<template>
    <AuthenticatedLayout>
        <div class="bg-white grid grid-cols-12 min-h-[calc(100vh-4rem)]">
            <!-- слева список чатов (пока заглушка) -->
            <aside class="col-span-3 bg-white shadow-lg relative z-10 max-h-[calc(100vh-4rem)] overflow-y-auto">
                <div v-for="bot in botsRef" :key="bot.id" class="">
                    <h2 class="bg-gray-50 shadow-sm py-1.5 pl-6" >Бот "{{ bot.username }}"</h2>
                    <div class="my-3">
                        <div v-for="chat in bot.telegram_chats">

                            <Link v-if="user.id === chat.user_id || user.role === 'admin' " :href="route('chat.show', chat.id)"
                                  class="flex items-center gap-3 py-2 px-4 bg-white hover:bg-gray-50 transition duration-50">

                                <div class="flex items-center justify-center h-10 w-10 rounded-full bg-red-700 text-gray-100 font-semibold text-2xl">
                                    {{ getInitials(chat) }}
                                </div>

                                <div class="flex flex-1 flex-col gap-2 truncate">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[13px] font-bold text-gray-800">{{ chat.telegram_user?.username || chat.telegram_user?.first_name }}, {{chat.ticket_id}}</span>
                                        <span class="text-[11px] text-gray-400">{{ chat.last_message_in_human }}</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-[13px] text-gray-400 truncate" >{{ chat.last_message_in_text }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] text-gray-400" >{{ ticketTypeLabel(chat.ticket_type) }}</span>
                                            <div v-if="chat.status === 'open'" class="w-2 h-2 bg-blue-700 rounded-full"></div>
                                            <div v-if="chat.has_new" class="w-2 h-2 bg-red-700 rounded-full"></div>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                            <!--                        <div v-else class="p-3 rounded-xl my-2 bg-slate-100 flex justify-between">-->
                            <!--                            <Link :href="route('chat.show', chat.id)" >-->
                            <!--                                Чат с {{ chat.telegram_user?.username || chat.telegram_user?.first_name }}-->
                            <!--                            </Link>-->
                            <!--                        </div>-->
                        </div>
                    </div>
                </div>
                <h2 class="bg-gray-50 shadow-sm py-1.5 pl-6" >Закрытые обращения</h2>
                <div v-if="closedChats.length" class="my-3">
                    <div v-for="chat in closedChats" :key="'closed-' + chat.id" class="px-6 py-1">
                        <Link :href="route('chat.show', chat.id)" class="flex justify-between text-sm text-gray-600 hover:text-gray-900">
                            <div>{{ chat.telegram_user?.username || chat.telegram_user?.first_name }}</div>
                            <div>{{chat.last_message_in_human}}</div>
                        </Link>
                    </div>
                </div>

                <div v-else class="px-6 py-2 text-sm text-gray-400">
                    Нет закрытых обращений
                </div>
            </aside>

            <!-- окно чата -->
            <section ref="scrollEl" class="col-span-9 flex flex-col">
                <!-- лента сообщений -->
                <div  class="flex-1 overflow-y-auto space-y-2">
                </div>

            </section>
        </div>
    </AuthenticatedLayout>
</template>

