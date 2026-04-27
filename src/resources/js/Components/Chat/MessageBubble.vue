<script setup>
import Attachment from "@/Components/Chat/Attachment.vue";

defineProps({
    message: Object
})

</script>

<template>
    <div
        class="max-w-[85%] sm:max-w-[70%] min-w-[calc(4rem)] px-2 sm:px-3 py-1.5 sm:py-2 rounded-2xl shadow-md text-xs sm:text-sm whitespace-pre-wrap break-words"
        :class="message.direction === 'out'
                    ? 'bg-red-700 border border-red-600 text-white rounded-br-md'
                    : 'bg-gray-50 border border-gray-50 text-gray-900 rounded-bl-md'"
    >
        <div v-if="message.attachments_urls?.length" class="grid gap-2 justify-items-center"
             :class="message.attachments_urls.length === 1 ? 'grid-cols-1' : 'grid-cols-2'">
            <Attachment v-for="attachment_url in message.attachments_urls"
                        :src="attachment_url"></Attachment>
        </div>

        <p v-if="message.text">{{ message.text }}</p>
        <div class="mt-1 opacity-70 text-[11px] text-right">{{ message.time_human }}</div>
    </div>
</template>

<style scoped>

</style>
