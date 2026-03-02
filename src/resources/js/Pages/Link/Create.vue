<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import {useForm} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const form = useForm({
    telegram_id: ''
})

const submit = () => {
    form.post(route('link.store'));
};

</script>

<template>
    <AuthenticatedLayout>

        <form @submit.prevent="submit">
            <div class="w-1/3 mx-auto py-6">
                <InputLabel for="telegram_id" value="Введите Telegram ID (Узнать ID - команда в боте /myid)"></InputLabel>
                <TextInput
                    id="telegram_id"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.telegram_id"
                    required
                    autofocus
                />
                <p v-if="$page.props.flash?.success" class="text-green-600">
                    {{ $page.props.flash.success }}
                </p>
                <div class="mt-4 flex items-center justify-end">
                    <PrimaryButton
                        class="ms-4"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Привязать
                    </PrimaryButton>
                </div>
            </div>
        </form>

    </AuthenticatedLayout>
</template>

<style scoped>

</style>
