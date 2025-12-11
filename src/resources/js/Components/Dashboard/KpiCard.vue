<script setup>

const props = defineProps({
    title: {
        type: String
    },
    value: {
        type: null
    }
})
</script>

<template>
    <div class="rounded-2xl bg-white p-4 shadow-md">
        <div class="text-sm font-semibold text-gray-500">{{ title }}</div>
        <div class="text-3xl font-semibold mt-1">{{ value }}</div>
    </div>
</template>

<style scoped>

</style>
