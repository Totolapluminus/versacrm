<script setup>
import {PaperAirplaneIcon, PaperClipIcon, XCircleIcon} from "@heroicons/vue/24/solid/index.js";
import {computed} from "vue";

const props = defineProps({
    fileInputRef: Function,
    draft: String,
    pickedFiles: Array,
    isDisabled: Boolean,
    textareaPlaceholder: String
})

const emit = defineEmits([
    'update:fileInput',
    'update:draft',
    'pick-files',
    'open-file-picker',
    'remove-file',
    'send',
    'paste'
])

const draftModel = computed({
    get: () => props.draft,
    set: (value) => {
        emit('update:draft', value)
    },
})

function onDraftKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        emit('send')
    }
}

function onSubmit() {
    emit('send')
}

function onPaste(e) {
    emit('paste', e)
}

function onRemoveFile(index) {
    emit('remove-file', index)
}

function onPickFiles(e) {
    emit('pick-files', e)
}

function onOpenFilePicker() {
    emit('open-file-picker')
}

</script>

<template>
    <form
        @submit.prevent="onSubmit"
        class="p-2 sm:p-3 flex flex-wrap sm:flex-nowrap gap-2 border-t items-center"
    >
                    <textarea
                        v-model="draftModel"
                        @paste="onPaste"
                        @keydown="onDraftKeydown"
                        rows="1"
                        type="text"
                        :disabled="isDisabled"
                        :placeholder="textareaPlaceholder"
                        class="flex-1 min-w-0 px-3 sm:px-4 py-2 rounded-xl border-none focus:outline-none focus:ring-0 focus:ring-blue-200 text-xs sm:text-sm resize-none"
                    />
        <div v-if="pickedFiles.length"
             class="w-full sm:w-auto text-xs text-gray-500 max-w-full sm:max-w-[250px] order-3 sm:order-none">
            <div v-for="(file, i) in pickedFiles"
                 :key="file.name + file.size + i"
                 @click="onRemoveFile(i)" class="cursor-pointer relative group"
            >
                <div class="truncate">{{ file.name }}</div>
                <XCircleIcon
                    class="hidden absolute rounded-full size-5 text-red-400 -top-1.5 -right-2.5 group-hover:block"></XCircleIcon>
            </div>
        </div>
        <input
            :ref="fileInputRef"
            type="file"
            multiple
            accept="image/*"
            @change="onPickFiles"
            :disabled="isDisabled"
            class="hidden"
        />
        <button
            type="button"
            @click="onOpenFilePicker"
            class="flex justify-center items-center h-10 sm:w-15 sm:h-10 px-3 sm:px-4 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition"
            :disabled="isDisabled"
        >
            <PaperClipIcon class="size-4 sm:size-5 text-gray-700"/>
        </button>
        <button
            type="submit"
            class="flex justify-center items-center h-10 sm:w-15 sm:h-10 px-3 sm:px-4 py-2 rounded-xl bg-red-700 text-white hover:bg-red-800 transition duration-50"
            :disabled="isDisabled"
        >
            <PaperAirplaneIcon class="text-white size-4 sm:size-5"></PaperAirplaneIcon>
        </button>
    </form>
</template>

<style scoped>

</style>
