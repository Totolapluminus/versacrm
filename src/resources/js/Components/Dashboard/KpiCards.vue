<script setup>
import { computed } from 'vue'
import KpiCard from '@/Components/Dashboard/KpiCard.vue'

import {
    Bot,
    CheckCircle2,
    Clock3,
    Gauge,
    Mail,
    MessageCircle,
    Smile,
    Wrench,
} from 'lucide-vue-next'

const props = defineProps({
    kpis: {
        type: Object,
        required: true,
    },
    start: {
        type: Number,
        default: 0,
    },
    limit: {
        type: Number,
        default: 8,
    },
    gridClass: {
        type: String,
        default: 'grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4',
    },
})

const formatTime = (seconds) => {
    if (!seconds || seconds === 0) return '0:00'

    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60

    return `${mins}:${secs.toString().padStart(2, '0')}`
}

const cards = computed(() => [
    {
        key: 'newTickets',
        title: 'Новые обращения',
        value: props.kpis.newTickets,
        icon: MessageCircle,
        tone: 'indigo',
    },
    {
        key: 'activeTickets',
        title: 'Обращения в работе',
        value: props.kpis.activeTickets,
        icon: Gauge,
        tone: 'emerald',
    },
    {
        key: 'closedTickets',
        title: 'Закрытые обращения',
        value: props.kpis.closedTickets,
        icon: CheckCircle2,
        tone: 'amber',
    },
    {
        key: 'totalMessages',
        title: 'Все сообщения',
        value: props.kpis.totalMessages,
        icon: Mail,
        tone: 'blue',
    },
    {
        key: 'totalBots',
        title: 'Доступно ботов',
        value: props.kpis.totalBots,
        icon: Bot,
        tone: 'red',
    },
    {
        key: 'mostLoadedBot',
        title: 'Самый загруженный бот',
        value: props.kpis.mostLoadedBot ?? '—',
        icon: Wrench,
        tone: 'rose',
    },
    {
        key: 'avgResponseTime',
        title: 'Время первого ответа',
        value: formatTime(props.kpis.avgResponseTime),
        icon: Clock3,
        tone: 'violet',
    },
    {
        key: 'avgCloseTime',
        title: 'Время решения заявки',
        value: formatTime(props.kpis.avgCloseTime),
        icon: Smile,
        tone: 'cyan',
    },
])

const visibleCards = computed(() => {
    return cards.value.slice(props.start, props.start + props.limit)
})

</script>

<template>
    <div class="grid" :class="gridClass">
        <KpiCard
            v-for="card in visibleCards"
            :key="card.key"
            :title="card.title"
            :value="card.value"
            :icon="card.icon"
            :tone="card.tone"
        />
    </div>
</template>
