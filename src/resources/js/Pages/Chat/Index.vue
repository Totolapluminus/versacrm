<script setup>
import {ref, onMounted, onUnmounted, toRaw} from 'vue'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {usePage} from "@inertiajs/vue3";
import ChatSidebar from "@/Components/Chat/ChatSidebar.vue";
import {useChatListWebsocket} from "@/Composables/useChatListWebsocket.js";

const {props} = usePage()
const user = props.user ?? 'none'

const scrollEl = ref(null)

const botsRef = ref(structuredClone(toRaw(props.bots)))

const scrollToBottom = () => {
    if (!scrollEl.value) return
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
}

onMounted(() => scrollToBottom())

useChatListWebsocket(botsRef)

</script>

<template>
    <AuthenticatedLayout>
        <div class="bg-white lg:grid lg:grid-cols-12 min-h-[calc(100vh-64px)] relative">
            <!-- слева список чатов -->
            <ChatSidebar :botsRef="botsRef"></ChatSidebar>

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
