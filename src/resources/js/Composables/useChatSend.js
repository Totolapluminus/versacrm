import {ref} from "vue";
import axios from "axios";

export function useChatSend(messages, currentChatDbId, currentChatTgId, onAfterSend = null) {
    const fileInput = ref(null)
    const pickedFiles = ref([])
    const draft = ref('')

    function onPickFiles(e) {
        pickedFiles.value = e.target.files ? Array.from(e.target.files) : []
    }

    function openFilePicker() {
        fileInput.value.click()
    }

    function onPaste(e) {
        const items = e.clipboardData?.items
        if (!items) return

        for (const item of items) {
            if (item.type && item.type.startsWith('image/')) {
                const blob = item.getAsFile()
                if (!blob) continue

                // превращаем в File (чтобы имя было нормальное)
                const ext = blob.type.split('/')[1] || 'png'
                const file = new File([blob], `paste-${Date.now()}.${ext}`, {type: blob.type})
                pickedFiles.value.push(file)

                // чтобы не вставлялся "пустой символ" в input
                e.preventDefault()
                break
            }
        }
    }

    function removeFile(index) {
        if (index < 0 || index >= pickedFiles.value.length) return

        pickedFiles.value.splice(index, 1)

        // если удалили всё — сбросить input, чтобы можно было снова выбрать те же файлы
        if (pickedFiles.value.length === 0 && fileInput.value) {
            fileInput.value.value = ''
        }
    }

    async function send(customText = null) {
        const text = customText ?? draft.value.trim()
        const files = customText ? [] : pickedFiles.value

        if (!text && files.length === 0) return

        const tmpId = `tmp-${Date.now()}`
        const optimistic = {
            id: tmpId,
            direction: 'out',
            text: text || null,
            telegram_chat_db_id: currentChatDbId,
            telegram_chat_tg_id: currentChatTgId,
            attachments_urls: files.length ? files.map(f => URL.createObjectURL(f)) : [],
        }

        messages.value.push(optimistic)

        if (!customText) {
            draft.value = ''
            pickedFiles.value = []
            if (fileInput.value) fileInput.value.value = ''
        }

        try {
            const formData = new FormData()
            formData.append('direction', 'out')
            if (text) formData.append('text', text)
            formData.append('telegram_chat_db_id', currentChatDbId)
            formData.append('telegram_chat_tg_id', currentChatTgId)

            for (const file of files) {
                formData.append('attachments[]', file)
            }

            const response = await axios.post(`/api/chat/${currentChatDbId}`, formData)

            const idx = messages.value.findIndex(m => m.id === tmpId)
            if (idx !== -1) {
                for (const url of (messages.value[idx].attachments_urls || [])) {
                    if (typeof url === 'string' && url.startsWith('blob:')) {
                        URL.revokeObjectURL(url)
                    }
                }
                messages.value[idx] = response.data
            }

            if (typeof onAfterSend === 'function') {
                onAfterSend(response.data)
            }
        } catch (e) {
            const idx = messages.value.findIndex(m => m.id === tmpId)
            if (idx !== -1) {
                for (const url of (messages.value[idx].attachments_urls || [])) {
                    if (typeof url === 'string' && url.startsWith('blob:')) {
                        URL.revokeObjectURL(url)
                    }
                }
                messages.value.splice(idx, 1)
            }

            console.error('Не удалось отправить сообщение', e)
        }
    }

    return {
        fileInput,
        pickedFiles,
        draft,
        onPickFiles,
        openFilePicker,
        onPaste,
        removeFile,
        send,
    }
}
