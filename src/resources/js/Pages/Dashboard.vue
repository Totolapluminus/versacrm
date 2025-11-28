<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {Head, usePage, router} from '@inertiajs/vue3'
import {computed, onMounted, ref, watch} from 'vue'
import {Line} from 'vue-chartjs'
import {Bar} from 'vue-chartjs'
import {
    Chart as ChartJS, Title, Tooltip, Legend,
    LineElement, BarElement, PointElement, CategoryScale, LinearScale
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, BarElement, PointElement, CategoryScale, LinearScale)

const {props} = usePage()
const user = props.user
const operators = ref(props.operators ?? [])
const selectedOperatorId = ref(props.selectedOperatorId ?? '')
const kpis = ref(props.kpis)
const chart = ref(props.chart)
const weeklyChart = ref(props.weeklyChart)

onMounted(() => {
    const token = props.flash?.api_token

    if (token) {
        localStorage.setItem('token', token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        console.log('Токен сохранён и установлен в axios')
    } else {
        const saved = localStorage.getItem('token')
        if (saved) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${saved}`
            console.log('Токен взят из localStorage')
        }
    }
})

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

const weeklyBarData = computed(() => ({
    labels: weeklyChart.value.labels ?? [],
    datasets: [
        {
            label: 'Обращения за неделю',
            data: weeklyChart.value.series ?? [],
            borderWidth: 1,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
        }
    ]
}));

const weeklyBarOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        }
    },
    scales: {
        x: {
            ticks: { maxRotation: 0, minRotation: 0 }
        },
        y: {
            beginAtZero: true
        }
    }
};

// При изменении оператора обновляем данные через Inertia
watch(selectedOperatorId, (newVal) => {
    router.get(route('dashboard'), { operator_id: newVal }, { preserveState: false })
})
</script>

<template>
    <Head title="Dashboard"/>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Дашборд</h2>
        </template>

        <!-- Оператор -->
        <div v-if="user.role === 'operator'" class="space-y-6 py-8 px-40">

            <div class="rounded-2xl bg-white p-4 shadow-sm h-80">
                <Bar :data="weeklyBarData" :options="weeklyBarOpts"/>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Новые обращения</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.newTickets }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Обрабатываемые обращения</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.activeTickets }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Закрытые обращения</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.closedTickets }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Закрепленные боты</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.totalBots }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Все сообщения</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.totalMessages }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Среднее время первого ответа (с.)</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.avgResponseTime }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Самый загруженный бот</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.mostLoadedBot }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Среднее время решения заявки (с.)</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.avgCloseTime }}</div>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm h-80">
                <Line :data="lineData" :options="lineOpts"/>
            </div>
        </div>

        <!-- Админ -->
        <div v-else-if="user.role === 'admin'" class="space-y-6 py-8 px-40">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-semibold mb-4">
                    {{ selectedOperatorId ? 'Статистика оператора' : 'Общая статистика системы' }}
                </h3>

                <select v-model="selectedOperatorId"
                        class="border rounded-lg px-3 py-2 text-sm focus:ring focus:ring-blue-200">
                    <option value="">Общая статистика</option>
                    <option v-for="op in operators" :key="op.id" :value="op.id">
                        {{ op.name }}
                    </option>
                </select>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm h-80">
                <Bar :data="weeklyBarData" :options="weeklyBarOpts"/>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Открытых обращений</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.newTickets }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Обрабатываемых обращений</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.activeTickets }}</div>
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
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Самый загруженный бот</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.mostLoadedBot }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Среднее время первого ответа (с.)</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.avgResponseTime }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Среднее время решения заявки (с.)</div>
                    <div class="text-3xl font-semibold mt-1">{{ kpis.avgCloseTime }}</div>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm h-80">
                <Line :data="lineData" :options="lineOpts" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
