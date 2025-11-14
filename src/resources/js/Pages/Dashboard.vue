<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, usePage} from '@inertiajs/vue3';
import axios from "axios";

const { props } = usePage()
const token = props.flash?.api_token
console.log(token)

if (token) {
    localStorage.setItem('crm_token', token)
    axios.defaults.headers.common.Authorization = `Bearer ${token}`
}

// Chart.js + vue-chartjs
import {
    Chart as ChartJS,
    Title, Tooltip, Legend,
    LineElement, PointElement,
    CategoryScale, LinearScale
} from 'chart.js'
ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale)
import { Line } from 'vue-chartjs'
import {computed, onMounted, ref} from "vue";

// ---- state ----
const loading = ref(true)
const kpis = ref({
    newTickets: 0,
    closedTickets: 0,
    totalBots: 0,
    totalMessages: 0, // 4-я карточка: среднее время ответа
})


// Линейный график «обращения по ботам по дням»
const lineData = ref({ labels: [], datasets: [] })
const lineOpts = {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: { legend: { position: 'bottom' }, title: { display: true, text: 'Обращения по ботам' } },
    scales: { x: { ticks: { autoSkip: true, maxTicksLimit: 10 } }, y: { beginAtZero: true, precision: 0 } }
}

// GET /api/dashboard/kpis -> { newTickets, closedTickets, totalBots, totalMessages }
// GET /api/dashboard/by-bot-daily -> { labels: [...], series: [{label:'@bot1', data:[...]}, ...] }

async function loadData() {
    loading.value = true
    try {
        const [k1, g1] = await Promise.all([
            axios.get('/api/dashboard/getKpi'),
            axios.get('/api/dashboard/getKpiByBot'),
        ])

        // KPI
        kpis.value = {
            newTickets: k1.data?.newTickets ?? 0,
            closedTickets: k1.data?.closedTickets ?? 0,
            totalBots: k1.data?.totalBots ?? 0,
            totalMessages: k1.data?.totalMessages ?? 0,
        }

        // Line
        const labels = g1.data?.labels ?? []
        const series = g1.data?.series ?? [] // [{label, data}]
        lineData.value = {
            labels,
            datasets: series.map(s => ({
                label: s.label,
                data: s.data,
                borderWidth: 2,
                tension: 0.2,
                fill: false,
                pointRadius: 2,
            }))
        }
    } catch (e) {
        // fallback
        console.error(e)
        kpis.value = { newTickets: 0, closedTickets: 0, totalBots: 0, totalMessages: 0 }
        lineData.value = {
            labels: ['2025-11-01', '2025-11-02', '2025-11-03'],
            datasets: [
                { label: '@bot_demo', data: [3, 5, 2], borderWidth: 2, tension: 0.2, fill: false, pointRadius: 2 }
            ]
        }
    } finally {
        loading.value = false
    }
}

onMounted(loadData)

const avgResponseHuman = computed(() => {
    const s = Number(kpis.value.avgResponseSec || 0)
    if (s < 60) return `${s}s`
    const m = Math.floor(s / 60), r = s % 60
    return r ? `${m}m ${r}s` : `${m}m`
})

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Дашборд
            </h2>
        </template>

        <div class="space-y-6 py-8 px-40">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Новые обращения</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.newTickets }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Закрыто вопросов</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.closedTickets }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Кол-во активных ботов</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.totalBots }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Все сообщения</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.totalMessages }}</div>
                </div>
            </div>

            <!-- Line Chart -->
            <div class="rounded-2xl bg-white p-4 shadow-sm h-80">
                <div v-if="loading" class="h-full grid place-items-center text-gray-500">Загрузка…</div>
                <Line v-else :data="lineData" :options="lineOpts" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
