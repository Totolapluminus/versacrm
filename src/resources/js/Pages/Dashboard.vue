<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'

import KpiCards from '@/Components/Dashboard/KpiCards.vue'
import KpiChartLine from '@/Components/Dashboard/KpiChartLine.vue'

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'
import KpiChartDonut from "@/Components/Dashboard/KpiChartDonut.vue";

const { props } = usePage()

const user = props.user
const operators = ref(props.operators ?? [])
const selectedOperatorId = ref(props.selectedOperatorId ? String(props.selectedOperatorId) : 'all')
const kpis = ref(props.kpis)
const chart = ref(props.chart)
const weeklyChart = ref(props.weeklyChart)
const domainChart = ref(props.domainChart)

const pageTitle = computed(() => {
    if (user.role === 'operator') {
        return 'Статистика оператора'
    }

    return selectedOperatorId.value === 'all'
        ? 'Общая статистика системы'
        : 'Статистика оператора'
})

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

watch(selectedOperatorId, (newVal) => {
    router.get(
        route('dashboard'),
        {
            operator_id: newVal === 'all' ? '' : newVal,
        },
        {
            preserveState: false,
        },
    )
})
</script>

<template>
    <Head title="Статистика" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-950">
                        {{ pageTitle }}
                    </h1>
                </div>

                <div v-if="user.role === 'admin'" class="w-full sm:w-72">
                    <Select v-model="selectedOperatorId">
                        <SelectTrigger class="h-10 rounded-xl border-slate-200 bg-white">
                            <SelectValue placeholder="Выберите оператора" />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem value="all">
                                Общая статистика
                            </SelectItem>

                            <SelectItem
                                v-for="op in operators"
                                :key="op.id"
                                :value="String(op.id)"
                            >
                                {{ op.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <KpiCards
                :kpis="kpis"
                :start="0"
                :limit="4"
                grid-class="grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
            />

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <KpiCards
                    :kpis="kpis"
                    :start="4"
                    :limit="4"
                    grid-class="grid-cols-1 gap-5 sm:grid-cols-2"
                />

                <KpiChartDonut
                    title="Виды обращений"
                    :chart="domainChart"
                    :height="320"
                    class="h-full"
                />
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <KpiChartLine
                    title="Новых обращений за неделю"
                    :chart="weeklyChart"
                />

                <KpiChartLine
                    title="Новых сообщений за день"
                    :chart="chart"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
