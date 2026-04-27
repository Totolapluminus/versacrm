<script setup>
import { computed } from 'vue'

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    currentChat: {
        type: Object,
        required: true,
    },
    operators: {
        type: Array,
        default: () => [],
    },
    chatStatus: {
        type: String,
        default: '',
    },
    chatOperator: {
        type: [String, Number, null],
        default: null,
    },
    quickReplies: {
        type: Array,
        default: () => [],
    },
    compact: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits([
    'update:chatStatus',
    'update:chatOperator',
    'update-status',
    'update-operator',
    'send-quick-reply',
    'delete-chat',
])

const canManageChat = computed(() =>
    props.user?.id === props.currentChat?.user_id || props.user?.role === 'admin'
)

const statusModel = computed({
    get: () => props.chatStatus,
    set: (value) => {
        emit('update:chatStatus', value)
        emit('update-status', value)
    },
})

const operatorModel = computed({
    get: () => props.chatOperator,
    set: (value) => {
        emit('update:chatOperator', value)
        emit('update-operator', value)
    },
})

function onQuickReply(reply) {
    emit('send-quick-reply', reply)
}

function onDeleteChat() {
    emit('delete-chat')
}
</script>

<template>
    <div v-if="canManageChat" :class="compact ? 'p-4 flex flex-col gap-4' : 'bg-white rounded-2xl shadow-md p-4 m-4 border border-gray-100 flex flex-col gap-4'">
        <template v-if="!compact">
            <h3 class="font-semibold text-gray-700 mb-2">Опции чата</h3>
        </template>

        <div>
            <label :for="compact ? 'status-mobile' : 'status'" class="text-sm text-gray-600 mb-1 block">
                Статус:
            </label>

            <select
                :id="compact ? 'status-mobile' : 'status'"
                v-model="statusModel"
                class="w-full border rounded-lg px-3 py-2 lg:py-1 text-sm focus:outline-none focus:ring focus:ring-blue-200"
            >
                <option value="open">Открыт</option>
                <option value="in_progress">В работе</option>
                <option value="closed">Закрыт</option>
            </select>
        </div>

        <div>
            <label :for="compact ? 'operator-mobile' : 'operator'" class="text-sm text-gray-600 mb-1 block">
                Оператор:
            </label>

            <select
                :id="compact ? 'operator-mobile' : 'operator'"
                v-model="operatorModel"
                class="w-full border rounded-lg px-3 py-2 lg:py-1 text-sm focus:outline-none focus:ring focus:ring-blue-200"
            >
                <option disabled value="">Выберите оператора</option>
                <option
                    v-for="operator in operators"
                    :key="operator.id"
                    :value="operator.id"
                >
                    {{ operator.name }}
                </option>
            </select>
        </div>

        <div>
            <label class="text-sm text-gray-600 mb-2 block">Быстрые ответы:</label>

            <div class="flex flex-col gap-2">
                <button
                    v-for="reply in quickReplies"
                    :key="reply"
                    type="button"
                    @click="onQuickReply(reply)"
                    class="w-full text-left border rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-red-700 hover:text-white hover:border-red-700 transition"
                    :disabled="chatStatus === 'closed' || !canManageChat"
                >
                    {{ reply }}
                </button>
            </div>
        </div>

        <button
            v-if="chatStatus === 'closed'"
            type="button"
            @click="onDeleteChat"
            class="w-full bg-red-700 text-white rounded-lg px-3 py-2 text-sm font-semibold hover:bg-red-800 focus:outline-none focus:ring focus:ring-red-200"
        >
            Удалить чат
        </button>
    </div>

    <div
        v-else
        :class="compact ? 'px-4 py-6 text-center font-bold text-lg' : 'px-3 py-6 text-center font-bold text-xl'"
    >
        ЧАТ ПРИНАДЛЕЖИТ ДРУГОМУ ОПЕРАТОРУ
        <br><br>
        ОПЦИИ НЕДОСТУПНЫ
    </div>
</template>

<style scoped>

</style>
