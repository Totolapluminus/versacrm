<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale)

if (typeof window !== 'undefined') {
    const bodyFont = getComputedStyle(document.body).fontFamily
    ChartJS.defaults.font.family = bodyFont
}

const props = defineProps({
    chart: {
        type: Object,
        required: true
    },
    title: {
        type: String,
        required: true
    }
})

const lineData = computed(() => ({
    labels: props.chart.labels ?? [],
    datasets: props.chart.series?.map(s => ({
        label: s.label,
        data: s.data,
        borderWidth: 2,
        tension: 0.2,
        fill: false,
        pointRadius: 2,
        borderColor: 'rgba(255, 65, 60, 0.5)',
    })) ?? []
}))

const lineOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' },
        title: {
            display: true,
            text: props.title,   // вот тут твой текст
            align: 'center',
            padding: {
                top: 10,
                bottom: 20
            },
            font: {
                size: 20,         // размер заголовка
                weight: 600,

            },
            color: 'rgb(0,0,0)',
        }
    }
}
</script>

<template>
    <div class="rounded-2xl bg-white p-4 shadow-md h-80">
        <Line :data="lineData" :options="lineOpts" />
    </div>
</template>
