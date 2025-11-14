<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePage, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

const { props } = usePage();
const bots  = props.bots ?? [];
const users = props.users ?? [];

const selectedUserId = ref(users.length ? users[0].id : null);

const getAttachedIds = (user) => {
    const rel = user?.telegram_bots ?? [];
    return rel.map(b => b.id);
};

// реактивный текущий пользователь
const selectedUser = computed(() => users.find(u => u.id === selectedUserId.value) || null);

// множество выбранных бот-идов (для чекбоксов)
const selectedBotIds = ref(new Set(selectedUser.value ? getAttachedIds(selectedUser.value) : []));

// при смене пользователя — перезаполнить чекбоксы из его привязок
watch(selectedUserId, () => {
    const ids = selectedUser.value ? getAttachedIds(selectedUser.value) : [];
    selectedBotIds.value = new Set(ids);
});

// чек/анчек
const toggle = (botId) => {
    if (selectedBotIds.value.has(botId)) selectedBotIds.value.delete(botId);
    else selectedBotIds.value.add(botId);
};

// submit всей формы (один запрос на синхронизацию)
const submit = () => {
    if (!selectedUserId.value) return;

    const payload = {
        user_id: selectedUserId.value,
        bot_ids: Array.from(selectedBotIds.value),
    };

    router.post(route('assign.store'), payload, {
        preserveScroll: true,
        preserveState: false,
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-6 px-40 space-y-6">
            <h1 class="text-xl font-semibold">Закрепление ботов за операторами</h1>

            <!-- Оператор -->
            <div>
                <label class="block text-sm mb-1">Оператор</label>
                <select v-model="selectedUserId" class="border rounded px-3 py-2 w-full">
                    <option v-for="u in users" :key="u.id" :value="u.id">
                        {{ u.name || u.email || `User #${u.id}` }}
                    </option>
                </select>
            </div>

            <!-- Боты -->
            <div>
                <label class="block text-sm mb-2">Боты</label>
                <div class="border rounded divide-y max-h-96 overflow-auto">
                    <div v-for="b in bots" :key="b.id" class="flex items-center gap-3 p-2">
                        <input
                            type="checkbox"
                            :checked="selectedBotIds.has(b.id)"
                            @change="toggle(b.id)"
                        />
                        <span>Bot #{{ b.id }}</span>
                    </div>
                </div>
            </div>

            <!-- Кнопка применить -->
            <div class="flex justify-end">
                <button
                    type="button"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
                    @click="submit"
                >
                    Применить
                </button>
                <p v-if="$page.props.flash?.success" class="text-green-600">
                    {{ $page.props.flash.success }}
                </p>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
