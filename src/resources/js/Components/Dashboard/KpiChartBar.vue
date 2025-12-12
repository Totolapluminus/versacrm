<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

if (typeof window !== 'undefined') {
    const bodyFont = getComputedStyle(document.body).fontFamily
    ChartJS.defaults.font.family = bodyFont
}

const props = defineProps({
    weeklyChart: {
        type: Object,
        required: true
    },
    title: {
        type: String,
        required: true
    }
})
console.log(props)

const weeklyBarData = computed(() => ({
    labels: props.weeklyChart.labels ?? [],
    datasets: [
        {
            label: 'Обращения за неделю',
            data: props.weeklyChart.series ?? [],
            borderWidth: 1,
            backgroundColor: 'rgba(255, 55, 50, 0.6)',
        }
    ]
}))

const weeklyBarOpts = {
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
                weight: 600
            },
            color: 'rgb(0,0,0)',
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
}
</script>

<template>
    <div class="rounded-2xl bg-white p-4 shadow-md h-80">
        <Bar :data="weeklyBarData" :options="weeklyBarOpts" />
    </div>
</template>
