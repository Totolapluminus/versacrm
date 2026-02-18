<script setup>

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {ref} from "vue";

const searchData = ref([])
const searchInput = ref('')

async function getSearch() {
    await axios.get('/api/search',
        {
            params: {
                search: searchInput.value,
            }
        }
    ).then((res) => {
        searchData.value = res.data
        console.log(res)
    }).catch((error) => {
        console.log(error)
    })
}
</script>

<template>
    <AuthenticatedLayout>
        <div class="bg-white h-[100vh]">
            <div class="mx-36 py-4">
                <form
                    @submit.prevent="getSearch"
                    class="max-w-md mx-auto"
                >
                    <label for="search" class="block mb-2.5 text-sm font-medium text-heading sr-only "></label>
                    <div class="relative ">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                 width="24"
                                 height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                      d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                            </svg>
                        </div>
                        <input v-model="searchInput" type="search" id="search"
                               class="block w-full rounded-md p-3 ps-9 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body"
                               placeholder="Поиск по университету"/>
                        <button type="submit"
                                class="absolute end-1.5 bottom-1.5 bg-red-700 text-white bg-brand hover:bg-brand-strong box-border border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded text-xs px-3 py-1.5 focus:outline-none">
                            Найти
                        </button>
                    </div>
                </form>
            </div>


            <div class="mx-2">
                <table v-if="searchData.length"
                       class="table-fixed w-max bg-neutral-primary-soft shadow-xs rounded-base border border-default">
                    <tr class="bg-neutral-primary border-b border-default text-[10px] whitespace-nowrap">
                        <th class="leading-tight w-32 break-all whitespace-normal">
                            Email
                        </th>
                        <th class="px-1 leading-tight w-32 break-all whitespace-normal">
                            user_id
                        </th>
                        <th class="px-1 leading-tight">
                            Группы студента
                        </th>
                        <th class="px-1 leading-tight w-24 break-normal whitespace-normal">
                            Дата регистрации
                        </th>
                        <th class="px-1 leading-tight w-24 break-normal whitespace-normal">
                            Доступы отправлены
                        </th>
                        <th class="px-1 leading-tight w-24 break-normal whitespace-normal">
                            Кол-во студ профилей
                        </th>
                        <th class="px-1 leading-tight">
                            Логин
                        </th>
                        <th class="px-1 leading-tight">
                            Последний_вход
                        </th>
                        <th class="px-1 leading-tight">
                            Преподаватель
                        </th>
                        <th class="px-1 leading-tight">
                            Сотрудник
                        </th>
                        <th class="px-1 leading-tight w-16 break-normal whitespace-normal">
                            Статус аккаунта
                        </th>
                        <th class="px-1 leading-tight">
                            Студент
                        </th>
                        <th class="px-1 leading-tight">
                            Телефон
                        </th>
                        <th class="px-1 leading-tight">
                            ФИО
                        </th>
                    </tr>
                    <tr v-for="searchItem in searchData"
                        class="bg-neutral-primary border-b border-default text-[10px] whitespace-nowrap">
                        <td class="px-1 py-1 leading-tight w-32 break-all whitespace-normal">
                            {{ searchItem.Email }}
                        </td>
                        <td class="px-1 py-1 leading-tight w-32 break-all whitespace-normal">
                            {{ searchItem.user_id }}
                        </td>
                        <td class="px-1 py-1 leading-tight w-24 break-normal whitespace-normal">
                            {{ searchItem.Группы_студента }}
                        </td>
                        <td class="px-1 py-1 leading-tight w-24 break-all whitespace-normal">
                            {{ searchItem.Дата_регистрации }}
                        </td>
                        <td class="px-1 py-1 leading-tight">
                            {{ searchItem.Доступы_отправлены }}
                        </td>
                        <td class="px-1 py-1 leading-tight">
                            {{ searchItem.Кол_во_студ_профилей }}
                        </td>
                        <td class="px-1 py-1 leading-tight">
                            {{ searchItem.Логин }}
                        </td>
                        <td class="px-1 py-1 leading-tight">
                            {{ searchItem.Последний_вход }}
                        </td>
                        <td class="px-1 py-1 leading-tight">
                            {{ searchItem.Преподаватель }}
                        </td>
                        <td class="px-1 py-1 leading-tight">
                            {{ searchItem.Сотрудник }}
                        </td>
                        <td class="px-1 py-1 leading-tight">
                            {{ searchItem.Статус_аккаунта }}
                        </td>
                        <td class="px-1 py-1 leading-tight">
                            {{ searchItem.Студент }}
                        </td>
                        <td class="px-1 py-1 leading-tight w-24 break-normal whitespace-normal">
                            {{ searchItem.Телефон }}
                        </td>
                        <td class="px-1 py-1 leading-tight w-24 break-normal whitespace-normal">
                            {{ searchItem.ФИО }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
