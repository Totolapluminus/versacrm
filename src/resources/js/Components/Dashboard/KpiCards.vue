<script setup>
import { computed } from 'vue'
import KpiCard from "@/Components/Dashboard/KpiCard.vue";
const props = defineProps({
    kpis: {
        type: Object,
        required: true,
    },
})

const cards = computed(() => [
    { key: 'newTickets',      title: 'Новые обращения',                        value: props.kpis.newTickets },
    { key: 'activeTickets',   title: 'Обрабатываемые обращения',               value: props.kpis.activeTickets },
    { key: 'closedTickets',   title: 'Закрытые обращения',                     value: props.kpis.closedTickets },
    { key: 'totalBots',       title: 'Доступно ботов',                         value: props.kpis.totalBots },
    { key: 'totalMessages',   title: 'Все сообщения',                          value: props.kpis.totalMessages },
    { key: 'avgResponseTime', title: 'Среднее время первого ответа (с.)',      value: props.kpis.avgResponseTime },
    { key: 'mostLoadedBot',   title: 'Самый загруженный бот',                  value: props.kpis.mostLoadedBot },
    { key: 'avgCloseTime',    title: 'Среднее время решения заявки (с.)',      value: props.kpis.avgCloseTime },
])

</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard
            v-for="card in cards"
            :key="card.key"
            :title="card.title"
            :value="card.value"
        />
    </div>
</template>
