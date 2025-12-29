<script setup>
import { computed } from 'vue'
import KpiCard from "@/Components/Dashboard/KpiCard.vue";

import {
    ChatBubbleLeftEllipsisIcon,
    Cog6ToothIcon,
    CheckCircleIcon,
    ServerStackIcon,
    EnvelopeIcon,
    ClockIcon,
    WrenchScrewdriverIcon,
    FaceSmileIcon,
} from '@heroicons/vue/24/solid'

const props = defineProps({
    kpis: {
        type: Object,
        required: true,
    },
})
const formatTime = (seconds) => {
    if (!seconds || seconds === 0) return '0:00'
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

const cards = computed(() => [
    { key: 'newTickets',      title: 'Новые обращения',                   value: props.kpis.newTickets,                  icon: ChatBubbleLeftEllipsisIcon },
    { key: 'activeTickets',   title: 'Обращения в работе',                value: props.kpis.activeTickets,               icon: Cog6ToothIcon },
    { key: 'closedTickets',   title: 'Закрытые обращения',                value: props.kpis.closedTickets,               icon: CheckCircleIcon },
    { key: 'totalBots',       title: 'Доступно ботов',                    value: props.kpis.totalBots,                   icon: ServerStackIcon },
    { key: 'totalMessages',   title: 'Все сообщения',                     value: props.kpis.totalMessages,               icon: EnvelopeIcon },
    { key: 'avgResponseTime', title: 'Время первого ответа',              value: formatTime(props.kpis.avgResponseTime), icon: ClockIcon },
    { key: 'mostLoadedBot',   title: 'Самый загруженный бот',             value: props.kpis.mostLoadedBot,               icon: WrenchScrewdriverIcon },
    { key: 'avgCloseTime',    title: 'Время решения заявки',              value: formatTime(props.kpis.avgCloseTime),    icon: FaceSmileIcon },
])

</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard
            v-for="card in cards"
            :key="card.key"
            :title="card.title"
            :value="card.value"
            :icon="card.icon"
        />
    </div>
</template>
