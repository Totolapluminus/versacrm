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
        default: 280,
    },
})

const palette = [
    '#5B5CE2', // indigo
    '#3B82F6', // blue
    '#10B981', // emerald
    '#A855F7', // violet
    '#F59E0B', // amber
]

const series = computed(() => {
    return props.chart.series?.map((item) => ({
        name: item.label,
        data: item.data ?? [],
    })) ?? []
})

const categories = computed(() => {
    return props.chart.labels ?? []
})

const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: props.height,
        toolbar: {
            show: false,
        },
        zoom: {
            enabled: false,
        },
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 700,
            animateGradually: {
                enabled: true,
                delay: 120,
            },
            dynamicAnimation: {
                enabled: true,
                speed: 350,
            },
        },
        fontFamily: 'inherit',
        background: 'transparent',
    },

    colors: palette,

    dataLabels: {
        enabled: false,
    },

    stroke: {
        curve: 'smooth',
        width: 3,
        lineCap: 'round',
    },

    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 0.6,
            opacityFrom: 0.48,
            opacityTo: 0.08,
            stops: [0, 80, 100],
        },
    },

    markers: {
        size: 0,
        strokeWidth: 3,
        strokeColors: '#ffffff',
        hover: {
            size: 6,
            sizeOffset: 2,
        },
    },

    grid: {
        show: true,
        borderColor: '#E5E7EB',
        strokeDashArray: 5,
        padding: {
            top: 0,
            right: 14,
            bottom: 0,
            left: 8,
        },
    },

    xaxis: {
        categories: categories.value,
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
        tooltip: {
            enabled: false,
        },
        labels: {
            style: {
                colors: '#64748B',
                fontSize: '12px',
                fontWeight: 500,
            },
            formatter(value) {
                if (!value) return ''

                const str = String(value)

                // 2026-03-02 -> 02.03
                if (str.length >= 10) {
                    const [, month, day] = str.split('-')
                    return `${day}.${month}`
                }

                return str
            },
        },
    },

    yaxis: {
        min: 0,
        labels: {
            style: {
                colors: '#64748B',
                fontSize: '12px',
                fontWeight: 500,
            },
            formatter(value) {
                if (value >= 1000) {
                    return `${Math.round(value / 1000)}K`
                }

                return Math.round(value)
            },
        },
    },

    legend: {
        show: series.value.length > 1,
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
        shared: true,
        intersect: false,
        theme: 'light',
        x: {
            show: true,
        },
        y: {
            formatter(value) {
                return Number(value ?? 0).toLocaleString('ru-RU')
            },
        },
        marker: {
            show: true,
        },
        style: {
            fontSize: '12px',
            fontFamily: 'inherit',
        },
    },
}))
</script>

<template>
    <Card class="rounded-xl border-slate-200 bg-white shadow-sm">
        <CardHeader class="pb-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <CardTitle class="text-base font-semibold text-slate-950">
                        {{ title }}
                    </CardTitle>

                    <CardDescription
                        v-if="description"
                        class="mt-1 text-sm text-slate-500"
                    >
                        {{ description }}
                    </CardDescription>
                </div>
            </div>
        </CardHeader>

        <CardContent class="pt-2">
            <apexchart
                type="area"
                :height="height"
                :options="chartOptions"
                :series="series"
            />
        </CardContent>
    </Card>
</template>
