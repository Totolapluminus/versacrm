<script setup>
import {onBeforeUnmount, onMounted, ref} from "vue";

defineProps({
    src: { type: String, required: true},
    alt: { type: String, default: ""}
})

const isOpen = ref(false)

function onKey(e) {
    if (e.key === "Escape") isOpen.value = false
}

onMounted(() => window.addEventListener("keydown", onKey))
onBeforeUnmount(() => window.removeEventListener("keydown", onKey))


</script>

<template>
    <div>
        <img :src="src" :alt="alt" class="max-w-[300px] max-h-[300px] cursor-zoom-in rounded-lg" @click="isOpen = true"/>

        <Teleport to="body">
            <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 p-4" @click.self="isOpen = false">
                <img
                    :src="src"
                    :alt="alt"
                    class="max-h-[80vh] max-w-[80vw] rounded shadow cursor-zoom-out"
                    @click="isOpen = false"
                />
            </div>
        </Teleport>
    </div>
</template>

<style scoped>

</style>
