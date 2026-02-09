<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {Head, usePage, router} from '@inertiajs/vue3'
import {computed, onMounted, ref, watch} from 'vue'
import KpiCards from '@/Components/Dashboard/KpiCards.vue'
import KpiChartBar from "@/Components/Dashboard/KpiChartBar.vue";
import KpiChartLine from "@/Components/Dashboard/KpiChartLine.vue";

const {props} = usePage()
const user = props.user
const operators = ref(props.operators ?? [])
const selectedOperatorId = ref(props.selectedOperatorId ?? '')
const kpis = ref(props.kpis)
const chart = ref(props.chart)
const weeklyChart = ref(props.weeklyChart)

console.log(weeklyChart.value)

onMounted(() => {
    const token = props.flash?.api_token

    if (token) {
        localStorage.setItem('token', token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    } else {
        const saved = localStorage.getItem('token')
        if (saved) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${saved}`
        }
    }
})

// refresh
watch(selectedOperatorId, (newVal) => {
    router.get(route('dashboard'), { operator_id: newVal }, { preserveState: false })
})
</script>

<template>
    <Head title="Dashboard"/>
    <AuthenticatedLayout>
<!--        <template #header>-->
<!--            <h2 class="text-xl font-bold">Статистика</h2>-->
<!--        </template>-->

        <!-- Operator -->
        <div v-if="user.role === 'operator'" class="space-y-6 py-8 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <KpiChartLine title="Новых обращений за неделю" :chart="weeklyChart" />

            <KpiCards :kpis="kpis" />

            <KpiChartLine title="Всего сообщений за день" :chart="chart" />

        </div>

        <!-- Admin -->
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

            <KpiChartLine title="Новых обращений за неделю" :chart="weeklyChart" />

            <KpiCards :kpis="kpis" />

            <KpiChartLine title="Всего сообщений за день" :chart="chart" />

        </div>
    </AuthenticatedLayout>
</template>
