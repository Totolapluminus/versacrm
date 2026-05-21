<script setup>
import { computed } from 'vue'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card'

const props = defineProps({
    chart: {
        type: Object,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    height: {
        type: [String, Number],
        default: 300,
    },
})

const labels = computed(() => props.chart.labels ?? [])

const series = computed(() => {
    return (props.chart.series ?? []).map((value) => Number(value))
})

const total = computed(() => {
    return series.value.reduce((sum, value) => sum + value, 0)
})

const hasData = computed(() => {
    return series.value.length > 0 && total.value > 0
})

const chartOptions = computed(() => ({
    chart: {
        type: 'donut',
        fontFamily: 'inherit',
        background: 'transparent',
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 700,
        },
    },

    labels: labels.value,

    colors: [
        '#4F46E5', // indigo-600
        '#818CF8', // indigo-400
        '#C7D2FE', // indigo-200

        '#2563EB', // blue-600
        '#60A5FA', // blue-400
        '#BFDBFE', // blue-200

        '#CBD5E1', // slate-300 запасной нейтральный
    ],

    stroke: {
        width: 4,
        colors: ['#ffffff'],
    },

    dataLabels: {
        enabled: false,
    },

    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        fontSize: '13px',
        fontWeight: 500,
        labels: {
            colors: '#334155',
        },
        markers: {
            width: 10,
            height: 10,
            radius: 12,
        },
        itemMargin: {
            horizontal: 10,
            vertical: 6,
        },
    },

    tooltip: {
        enabled: true,
        theme: 'light',
        y: {
            formatter(value) {
                return `${Number(value ?? 0).toLocaleString('ru-RU')} обращ.`
            },
        },
        style: {
            fontSize: '12px',
            fontFamily: 'inherit',
        },
    },

    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,

                    name: {
                        show: true,
                        fontSize: '13px',
                        fontWeight: 500,
                        color: '#64748B',
                        offsetY: -4,
                    },

                    value: {
                        show: true,
                        fontSize: '24px',
                        fontWeight: 700,
                        color: '#020617',
                        offsetY: 4,
                        formatter(value) {
                            return Number(value ?? 0).toLocaleString('ru-RU')
                        },
                    },

                    total: {
                        show: true,
                        showAlways: true,
                        label: 'Всего',
                        fontSize: '13px',
                        fontWeight: 500,
                        color: '#64748B',
                        formatter() {
                            return total.value.toLocaleString('ru-RU')
                        },
                    },
                },
            },
        },
    },
}))
</script>

<template>
    <Card class="rounded-xl border-slate-200 bg-white shadow-sm">
        <CardHeader class="pb-2">
            <CardTitle class="text-base font-semibold text-slate-950">
                {{ title }}
            </CardTitle>

            <CardDescription
                v-if="description"
                class="mt-1 text-sm text-slate-500"
            >
                {{ description }}
            </CardDescription>
        </CardHeader>

        <CardContent>
            <div
                v-if="hasData"
                class="w-full"
            >
                <apexchart
                    type="donut"
                    :height="height"
                    :options="chartOptions"
                    :series="series"
                />
            </div>

            <div
                v-else
                class="flex h-[260px] items-center justify-center rounded-xl border border-dashed border-slate-200 text-sm text-slate-500"
            >
                Нет данных для отображения
            </div>
        </CardContent>
    </Card>
</template>
