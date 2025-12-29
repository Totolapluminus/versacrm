<script setup>

const props = defineProps({
    title: String,
    value: {
        type: null
    },
    icon: {
        // Vue-компонент иконки (Heroicons, Lucide и т.п.)
        type: [Object, Function],
        default: null
    }
})
</script>

<template>
    <div class="rounded-lg bg-white py-6 px-4 shadow-md flex items-center justify-between">
        <component
            v-if="icon"
            :is="icon"
            class="w-14 h-14 text-red-600"
        />

        <div class="flex flex-col text-right">
            <div class="text-sm font-semibold text-gray-500">{{ title }}</div>
            <div class="text-2xl font-bold">{{ value }}</div>
        </div>


    </div>
</template>

<style scoped>

</style>
