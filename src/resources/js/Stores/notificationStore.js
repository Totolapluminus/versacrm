import { defineStore } from 'pinia'
import {ref} from "vue";

export const useNotificationStore = defineStore('notifications', () => {
    const newChats = ref({})

    function addChat(chatId) {
        newChats.value[chatId] = true
    }

    function clearChat(chatId) {
        delete newChats.value[chatId]
    }

    const count = () => Object.keys(newChats.value).length

    return { newChats, addChat, clearChat, count }
})
