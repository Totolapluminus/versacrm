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

onUnmounted(() => window.Echo.leave(`store-telegram-chat`))

</script>


<template>
    <AuthenticatedLayout>
        <div class="bg-white grid grid-cols-12 min-h-[calc(100vh-4rem)]">
            <!-- слева список чатов (пока заглушка) -->
            <aside class="col-span-3 bg-white shadow-xl">
                <div v-for="bot in bots" :key="bot.id" class="">
                    <h2 class="border border-y border-gray-100 shadow-sm py-1.5 pl-6" >Бот "{{ bot.username }}"</h2>
                    <div class="my-2">
                        <div v-for="chat in bot.telegram_chats">

                            <Link v-if="user.id === chat.user_id" :href="route('chat.show', chat.id)"
                                  class="flex flex-col gap-1 py-2 px-6 bg-white hover:bg-gray-100 transition duration-50">

                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-md"> {{ chat.telegram_user?.username || chat.telegram_user?.first_name }} </span>
                                    <span class="text-sm text-slate-400">14:50</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-400" >Я понял!</span>
                                    <span v-if="chat.has_new" class="w-2 h-2 bg-red-400 rounded-full"></span>
                                    <span v-if="chat.status === 'open'" class="w-2 h-2 bg-red-400 rounded-full"></span>
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

