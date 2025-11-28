<script setup>
import {ref, onMounted, onUnmounted} from 'vue'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {usePage} from "@inertiajs/vue3";
import {Link} from "@inertiajs/vue3";


const {props} = usePage()
const user = props.user ?? 'none'
const bots = ref(props.bots ?? [])

console.log(user.id)
console.log(bots.value)

const draft = ref('')
const scrollEl = ref(null)

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
            const bot = bots.value.find(b => b.id === chat.telegram_bot_id)
            if(!bot) return

            const found = bot.telegram_chats.find(c => c.id === chat.id)
            if (found) {
                found.has_new = chat.has_new
                console.log(found)
                return
            }
            bot.telegram_chats.push(chat)
        })
})

onUnmounted(() => window.Echo.leave(`store-telegram-chat`))

</script>


<template>
    <AuthenticatedLayout>
        <div class="grid grid-cols-12 p-4 gap-4 h-[calc(100vh-4.5rem)]">
            <!-- слева список чатов (пока заглушка) -->
            <aside class="col-span-3 bg-white rounded-2xl p-3 shadow-sm">
                <div v-for="bot in bots" :key="bot.id" class="space-y-2">
                    <h2>Бот "{{bot.username}}"</h2>
                    <div v-for="chat in bot.telegram_chats">
                        <div v-if="user.id === chat.user_id" class="flex justify-between items-center p-3 rounded-xl my-2 bg-blue-200">
                            <Link :href="route('chat.show', chat.id)" >
                                Чат с {{ chat.telegram_user?.username || chat.telegram_user?.first_name }}
                            </Link>
                            <span v-if="chat.status === 'open'" class="text-green-400">Новое!</span>
                            <span
                                v-if="chat.has_new"
                                class="w-3 h-3 bg-red-500 rounded-full inline-block ml-2"
                            ></span>
                        </div>
                        <div v-else class="p-3 rounded-xl my-2 bg-slate-100 flex justify-between">
                            <Link :href="route('chat.show', chat.id)" >
                                Чат с {{ chat.telegram_user?.username || chat.telegram_user?.first_name }}
                            </Link>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- окно чата -->
            <section class="col-span-9 flex flex-col bg-white rounded-2xl shadow-sm">
                <!-- лента сообщений -->
                <div ref="scrollEl" class="flex-1 overflow-y-auto p-4 bg-slate-50 rounded-2xl space-y-2">
                </div>

            </section>
        </div>
    </AuthenticatedLayout>
</template>

