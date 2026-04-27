<script setup>
import {computed, ref} from "vue";
import {Link} from "@inertiajs/vue3";
import {useChatLabels} from "@/Composables/useChatLabels.js"

const props = defineProps({
    botsRef: {
        type: Array,
        default: () => [],
    },
    currentChat: {
        type: Object,
        default: null,
    },
})
const closedChats = computed(() =>
    props.botsRef.flatMap(bot => bot.telegram_chats ?? []).filter(chat => chat.status === 'closed')
)
const { ticketTypeLabel, ticketDomainLabel, getInitials } = useChatLabels()
const isClosedChatsOpen = ref(false)

</script>

<template>
    <aside class="lg:col-span-3 bg-white lg:shadow-lg relative z-10 h-auto lg:h-[calc(100vh-4rem)] overflow-visible lg:overflow-y-auto border-r border-gray-100 pb-1">
        <div v-for="bot in botsRef" :key="bot.id">
            <h2 class="bg-gray-50 shadow-sm py-1.5 pl-4 sm:pl-6 text-sm sm:text-base">Бот "{{ bot.username }}"</h2>
            <div class="my-3">
                <div v-for="chat in bot.telegram_chats.filter(chat => chat.status !== 'closed')" :key="chat.id">
                    <Link
                        :href="route('chat.show', chat.id)"
                        :class="[
                                    'flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2',
                                    chat.id === currentChat?.id
                                        ? 'bg-gray-50'
                                        : 'bg-white hover:bg-gray-50 transition duration-50'
                                ]"
                    >

                        <div class="flex items-center justify-center h-8 w-8 sm:h-10 sm:w-10 rounded-full bg-red-700 text-gray-100 font-semibold text-lg sm:text-2xl">
                            {{ getInitials(chat) }}
                        </div>

                        <div class="flex flex-1 flex-col gap-2 truncate">
                            <div class="flex justify-between items-center">
                                <span class="text-[12px] sm:text-[13px] font-bold text-gray-800 truncate">{{chat.telegram_user?.username || chat.telegram_user?.first_name }}, {{ chat.ticket_id }}</span>
                                <span class="text-[10px] sm:text-[11px] text-gray-400">{{ chat.last_message_in_human }}</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-[11px] sm:text-[13px] text-gray-400 truncate">{{chat.last_message?.text }}</span>
                                <div class="flex items-center gap-1.5">
                                    <div class="flex items-center gap-0.5">
                                        <span class="text-[10px] text-gray-400">{{ticketDomainLabel(chat.ticket_domain) }}</span>
                                        <span class="text-[10px] text-gray-400">|</span>
                                        <span class="text-[10px] text-gray-400">{{ticketTypeLabel(chat.ticket_type) }}</span>
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
</template>

<style scoped>

</style>
