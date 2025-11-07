<script setup>
import {ref, nextTick, watch, onMounted} from 'vue'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {usePage} from "@inertiajs/vue3";
import {Link} from "@inertiajs/vue3";


const {props} = usePage()
const bots = props.bots ?? []

console.log(bots)

const draft = ref('')
const scrollEl = ref(null)

const scrollToBottom = () => {
    if (!scrollEl.value) return
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
}

onMounted(() => scrollToBottom())

</script>


<template>
    <AuthenticatedLayout>
        <div class="grid grid-cols-12 pt-4 gap-4 h-[calc(100vh-8rem)]">
            <!-- слева список чатов (пока заглушка) -->
            <aside class="col-span-3 bg-white rounded-2xl p-3 shadow-sm">
                <div v-for="bot in bots" :key="bot.id" class="space-y-2">
                    <h2>Бот №{{bot.id}}</h2>
                    <div v-for="chat in bot.telegram_chats">
                        <div class="p-3 rounded-xl my-2 bg-slate-100">
                            <Link :href="route('chat.show', chat.id)" >
                                Чат с {{ chat.telegram_user?.username || 'Без имени' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- окно чата -->
            <section class="col-span-9 flex flex-col bg-white rounded-2xl shadow-sm">
                <!-- лента сообщений -->
                <div ref="scrollEl" class="flex-1 overflow-y-auto p-4 bg-slate-50 rounded-t-2xl space-y-2">
                </div>

            </section>
        </div>
    </AuthenticatedLayout>
</template>

