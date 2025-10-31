<script setup>
import {ref, nextTick, watch, onMounted} from 'vue'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {usePage} from "@inertiajs/vue3";
import axios from "axios";
import {Link} from "@inertiajs/vue3";


const {props} = usePage()
const chats = props.chats ?? []
const currentChatId = props.current_chat.chat_id ?? []
const messages = ref(props.current_chat.telegram_messages ?? [])

console.log(chats)
console.log(messages.value)
console.log(currentChatId)

const draft = ref('')
const scrollEl = ref(null)


const scrollToBottom = () => {
    if (!scrollEl.value) return
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
}


watch(messages, async () => {
    await nextTick();
    scrollToBottom()
})
onMounted(() => scrollToBottom())


async function send() {

    const text = draft.value.trim()
    if (!text) return

    // отображение сообщения на фронте заранее (временно)
    const tmpId = `tmp-${Date.now()}`
    const optimistic = {
        id: tmpId,
        direction: 'out',
        text: text,
        telegram_chat_raw_id: currentChatId,
    }

    messages.value.push(optimistic)
    draft.value = ''

    //отправка данных на бекенд
    try {
        const response = await axios.post(`/api/chat`, {
            direction: 'out',
            text: text,
            telegram_chat_raw_id: currentChatId,
        })
        console.log(response)

        //замена временного сообщения серверным
        const idx = messages.value.findIndex(m => m.id === tmpId)
        if (idx !== -1) messages.value[idx] = response.data
    } catch (e) {
        //откат, если ошибка
        const idx = messages.value.findIndex(m => m.id === tmpId)
        if (idx !== -1) messages.value.splice(idx, 1)
        console.error('Не удалось отправить сообщение', e)
    }
}

</script>


<template>
    <AuthenticatedLayout>
        <div class="grid grid-cols-12 gap-4 h-[calc(100vh-8rem)]">
            <!-- слева список чатов (пока заглушка) -->
            <aside class="col-span-3 bg-white rounded-2xl p-3 shadow-sm">
                <div v-for="chat in chats" :key="chat.id" class="space-y-2">
                    <div :class="[
                        'p-3 rounded-xl my-2',
                        chat.id === props.current_chat?.id ? 'bg-blue-400 text-white' : 'bg-slate-100'
                    ]">
                        <Link :href="route('chat.show', chat.id)" >
                            Чат с {{ chat.telegram_users.find(user => !user.is_bot)?.username || 'Без имени' }}
                        </Link>
                    </div>
                </div>
            </aside>

            <!-- окно чата -->
            <section class="col-span-9 flex flex-col bg-white rounded-2xl shadow-sm">
                <!-- лента сообщений -->
                <div ref="scrollEl" class="flex-1 overflow-y-auto p-4 bg-slate-50 rounded-t-2xl space-y-2">
                    <div v-for="m in messages" :key="m.id"
                         class="flex"
                         :class="m.direction === 'out' ? 'justify-end' : 'justify-start'">
                        <div
                            class="max-w-[70%] px-3 py-2 rounded-2xl shadow-sm whitespace-pre-wrap break-words"
                            :class="m.direction === 'out'
              ? 'bg-blue-600 text-white rounded-br-md'
              : 'bg-white text-slate-900 rounded-bl-md'">
                            <p>{{ m.text }}</p>
                            <div class="mt-1 text-[11px] opacity-70 text-right">{{ m.time }}</div>
                        </div>
                    </div>
                </div>

                <!-- поле ввода -->
                <form @submit.prevent="send" class="p-3 flex gap-2 rounded-b-2xl border-t bg-white">
                    <input
                        v-model="draft"
                        type="text"
                        placeholder="Напишите сообщение…"
                        class="flex-1 px-4 py-2 rounded-xl border focus:outline-none focus:ring focus:ring-blue-200"
                    />
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700">
                        Отправить
                    </button>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

