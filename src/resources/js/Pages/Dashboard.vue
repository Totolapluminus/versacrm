<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {Head, router, usePage} from '@inertiajs/vue3'
import {computed, ref} from 'vue'
import {Line} from 'vue-chartjs'
import {
    Chart as ChartJS, Title, Tooltip, Legend,
    LineElement, PointElement, CategoryScale, LinearScale
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale)

const {props} = usePage()
const user = props.user
const kpis = ref(props.kpis)
const chart = ref(props.chart)

const lineData = computed(() => ({
    labels: chart.value.labels ?? [],
    datasets: chart.value.series?.map(s => ({
        label: s.label,
        data: s.data,
        borderWidth: 2,
        tension: 0.2,
        fill: false,
        pointRadius: 2
    })) ?? []
}))

const lineOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {legend: {position: 'bottom'}}
}
</script>

<template>
    <Head title="Dashboard"/>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Дашборд</h2>
        </template>

        <div v-if="user.role === 'operator'" class="space-y-6 py-8 px-40">
            <h3 class="text-2xl font-semibold mb-4">Личная статистика</h3>

            <!-- KPI -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Новые обращения</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.newTickets }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Закрытые обращения</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.closedTickets }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Активные боты</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.totalBots }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Все сообщения</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.totalMessages }}</div>
                </div>
            </div>

            <!-- Line Chart -->
            <div class="rounded-2xl bg-white p-4 shadow-sm h-80">
                <Line :data="lineData" :options="lineOpts"/>
            </div>
        </div>

        <div v-else-if="user.role === 'admin'" class="space-y-6 py-8 px-40">
            <h3 class="text-2xl font-semibold mb-4">Общая статистика системы</h3>

            <!-- Общие KPI -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Открытых обращений</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.newTickets }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Закрытых обращений</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.closedTickets }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Активных ботов</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.totalBots }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Все сообщения</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.totalMessages }}</div>
                </div>
            </div>

            <!-- График обращений -->
            <div class="rounded-2xl bg-white p-4 shadow-sm h-80">
                <Line :data="lineData" :options="lineOpts" />
            </div>

        </div>
    </AuthenticatedLayout>
</template>
