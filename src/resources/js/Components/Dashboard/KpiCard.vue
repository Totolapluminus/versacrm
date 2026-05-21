<script setup>
import { computed } from 'vue'

import {
    Card,
    CardContent,
} from '@/Components/ui/card'

const props = defineProps({
    title: String,
    value: {
        type: null,
    },
    icon: {
        type: [Object, Function],
        default: null,
    },
    tone: {
        type: String,
        default: 'indigo',
    },
})

const toneClass = computed(() => {
    const tones = {
        indigo: 'bg-indigo-50 text-indigo-600',
        violet: 'bg-violet-50 text-violet-600',
        blue: 'bg-blue-50 text-blue-600',
        emerald: 'bg-emerald-100 text-emerald-600',
        amber: 'bg-amber-50 text-amber-600',
        rose: 'bg-rose-50 text-rose-600',
        red: 'bg-red-50 text-red-500',
        cyan: 'bg-cyan-100 text-cyan-600',
        slate: 'bg-slate-50 text-slate-600',
    }

    return tones[props.tone] ?? tones.indigo
})
</script>

<template>
    <Card class="h-full min-h-[160px] rounded-xl border-slate-200 bg-white shadow-sm">
        <CardContent class="flex h-full flex-col justify-between p-6">
            <div
                v-if="icon"
                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full"
                :class="toneClass"
            >
                <component :is="icon" class="h-7 w-7 stroke-[1.7]" />
            </div>

            <div class="mt-5">
                <p class="truncate text-sm font-medium text-slate-500">
                    {{ title }}
                </p>

                <div class="mt-2 truncate text-2xl font-semibold leading-none tracking-tight text-slate-950">
                    {{ value }}
                </div>
            </div>
        </CardContent>
    </Card>
</template>
