<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useNotificationStore } from '@/Stores/notificationStore.js'

import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { Avatar, AvatarFallback } from '@/Components/ui/avatar'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import {
    Sheet,
    SheetContent,
    SheetTrigger,
} from '@/Components/ui/sheet'
import { Separator } from '@/Components/ui/separator'
import { ScrollArea } from '@/Components/ui/scroll-area'
import { Input } from '@/Components/ui/input'

import {
    Bell,
    Bot,
    ChevronDown,
    LayoutDashboard,
    Link2,
    LogOut,
    Menu,
    MessageCircle,
    Search,
    ShieldCheck,
    UserPlus,
} from 'lucide-vue-next'

const page = usePage()
const user = page.props.auth.user

const notificationStore = useNotificationStore()
const mobileMenuOpen = ref(false)

const unreadCount = computed(() => notificationStore.count())

function logoutTokenClean() {
    localStorage.removeItem('token')
    delete axios.defaults.headers.common.Authorization
}

const navItems = computed(() => {
    const items = [
        {
            label: 'Статистика',
            href: route('dashboard'),
            icon: LayoutDashboard,
            active: route().current('dashboard'),
        },
        {
            label: 'Чаты',
            href: route('chat.index'),
            icon: MessageCircle,
            active: route().current('chat.index') || route().current('chat.show'),
            badge: unreadCount.value,
        },
        {
            label: 'Поиск',
            href: route('search.index'),
            icon: Search,
            active: route().current('search.index'),
        },
        {
            label: 'Телеграм ID',
            href: route('link.create'),
            icon: Link2,
            active: route().current('link.create'),
        },
    ]

    if (user?.role === 'admin') {
        items.push(
            {
                label: 'Доступ к ботам',
                href: route('assign.index'),
                icon: ShieldCheck,
                active: route().current('assign.index'),
            },
            {
                label: 'Новый оператор',
                href: route('register'),
                icon: UserPlus,
                active: route().current('register'),
            },
        )
    }

    return items
})

const userInitials = computed(() => {
    if (!user?.name) return 'U'

    return user.name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()
})

onMounted(() => {
    if (!user) return

    notificationStore.setChats(page.props.unreadChatIds ?? [])

    window.Echo.channel(`notification-on-message-to-user-${user.id}`)
        .listen('.notification-on-message-to-user', (res) => {
            notificationStore.addChat(res.chat_id)
            console.log('NOTIFICATION:', res)
        })

    console.log('Subscribed to', `notification-on-message-to-user-${user.id}`)
})

onUnmounted(() => {
    if (!user) return

    window.Echo.leave(`notification-on-message-to-user-${user.id}`)
})
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-950">
        <!-- Desktop sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-slate-200 bg-white/95 backdrop-blur lg:flex lg:flex-col"
        >
            <div class="flex h-16 items-center gap-3 px-6 py-10">
                <Link :href="route('dashboard')" class="flex items-center gap-3">

                        <img class="block h-12 w-auto fill-current text-gray-800" src="/storage/logo.png" alt="Logo">

                    <div class="leading-tight">
                        <div class="text-md font-semibold tracking-tight">
                            CRM.MELSU
                        </div>
                        <div class="text-xs text-muted-foreground">
                            Техподдержка МелГУ
                        </div>
                    </div>
                </Link>
            </div>


            <ScrollArea class="flex-1 px-3 py-4">
                <nav class="space-y-1">
                    <Button
                        v-for="item in navItems"
                        :key="item.label"
                        variant="ghost"
                        class="h-9 w-full justify-start gap-3 rounded-none px-3 text-sm font-md shadow-none !bg-transparent hover:!bg-transparent focus-visible:ring-0 focus-visible:ring-offset-0"
                        :class="item.active
                ? 'text-indigo-600 hover:text-indigo-600'
                : 'text-slate-700 hover:text-black'"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-5 w-5 shrink-0" />

                            <span class="flex-1 text-left">
                    {{ item.label }}
                </span>

                            <Badge
                                v-if="item.badge > 0"
                                variant="destructive"
                                class="ml-auto h-5 min-w-5 rounded-full px-1.5 text-[11px]"
                            >
                                {{ item.badge }}
                            </Badge>
                        </Link>
                    </Button>
                </nav>
            </ScrollArea>

<!--            <div class="border-t border-slate-200 p-4">-->
<!--                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">-->
<!--                    <div class="flex items-center gap-3">-->
<!--                        <Avatar class="h-9 w-9">-->
<!--                            <AvatarFallback class="bg-white text-xs font-semibold">-->
<!--                                {{ userInitials }}-->
<!--                            </AvatarFallback>-->
<!--                        </Avatar>-->

<!--                        <div class="min-w-0 flex-1">-->
<!--                            <div class="truncate text-sm font-medium">-->
<!--                                {{ user.name }}-->
<!--                            </div>-->
<!--                            <div class="truncate text-xs text-muted-foreground">-->
<!--                                {{ user.email }}-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
        </aside>

        <!-- Mobile topbar -->
        <div class="sticky top-0 z-40 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:hidden">
            <Sheet v-model:open="mobileMenuOpen">
                <SheetTrigger as-child>
                    <Button variant="ghost" size="icon" class="rounded-none bg-transparent hover:bg-transparent">
                        <Menu class="h-5 w-5 text-slate-700" />
                    </Button>
                </SheetTrigger>

                <SheetContent
                    side="left"
                    class="w-72 border-r border-slate-200 bg-white p-0 shadow-xl duration-300 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=open]:slide-in-from-left data-[state=closed]:slide-out-to-left"
                >
                    <div class="flex h-16 items-center gap-3 px-6 py-10">
                        <Link
                            :href="route('dashboard')"
                            class="flex items-center gap-3"
                            @click="mobileMenuOpen = false"
                        >
                            <img
                                class="block h-12 w-auto"
                                src="/storage/logo.png"
                                alt="Logo"
                            >

                            <div class="leading-tight">
                                <div class="text-md font-semibold tracking-tight">
                                    CRM.MELSU
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Техподдержка МелГУ
                                </div>
                            </div>
                        </Link>
                    </div>

                    <ScrollArea class="h-[calc(100vh-5rem)] px-3 py-4">
                        <nav class="space-y-1">
                            <Button
                                v-for="item in navItems"
                                :key="item.label"
                                variant="ghost"
                                class="h-9 w-full justify-start gap-3 rounded-none px-3 text-sm font-md shadow-none !bg-transparent hover:!bg-transparent focus-visible:ring-0 focus-visible:ring-offset-0"
                                :class="item.active
                            ? 'text-indigo-600 hover:text-indigo-600'
                            : 'text-slate-700 hover:text-black'"
                                as-child
                            >
                                <Link :href="item.href" @click="mobileMenuOpen = false">
                                    <component :is="item.icon" class="h-5 w-5 shrink-0" />

                                    <span class="flex-1 text-left">
                                {{ item.label }}
                            </span>

                                    <Badge
                                        v-if="item.badge > 0"
                                        variant="destructive"
                                        class="ml-auto h-5 min-w-5 rounded-full px-1.5 text-[11px]"
                                    >
                                        {{ item.badge }}
                                    </Badge>
                                </Link>
                            </Button>
                        </nav>
                    </ScrollArea>
                </SheetContent>
            </Sheet>

            <Link :href="route('dashboard')" class="flex items-center gap-3">
                <img
                    class="block h-10 w-auto"
                    src="/storage/logo.png"
                    alt="Logo"
                >

                <div class="leading-tight">
                    <div class="text-sm font-semibold tracking-tight">
                        CRM.MELSU
                    </div>
                    <div class="text-[11px] text-muted-foreground">
                        Техподдержка МелГУ
                    </div>
                </div>
            </Link>

            <Button variant="ghost" size="icon" class="relative rounded-none bg-transparent hover:bg-transparent">
                <Bell class="h-5 w-5 text-slate-700" />

                <span
                    v-if="unreadCount > 0"
                    class="absolute right-2 top-2 h-2 w-2 rounded-full bg-indigo-500"
                />
            </Button>
        </div>

        <!-- Main area -->
        <div class="lg:pl-72">
            <!-- Desktop topbar -->
            <header class="sticky top-0 z-30 hidden h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-6 py-10 backdrop-blur lg:flex">
                <div class="w-full max-w-md">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            class="h-10 rounded-xl bg-slate-50 pl-9"
                            placeholder="Поиск по чатам..."
                        />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="icon" class="relative rounded-xl">
                        <Bell class="h-5 w-5" />

                        <span
                            v-if="unreadCount > 0"
                            class="absolute right-2 top-2 h-2 w-2 rounded-full bg-indigo-500"
                        />
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" class="h-10 gap-3 rounded-xl px-2">
<!--                                <Avatar class="h-8 w-8">-->
<!--                                    <AvatarFallback class="bg-slate-100 text-xs font-semibold">-->
<!--                                        {{ userInitials }}-->
<!--                                    </AvatarFallback>-->
<!--                                </Avatar>-->

                                <div class="hidden max-w-40 text-left xl:block">
                                    <div class="truncate text-sm font-medium">
                                        {{ user.name }}
                                    </div>
                                    <div class="truncate text-xs text-muted-foreground">
                                        {{ user.role }}
                                    </div>
                                </div>

                                <ChevronDown class="h-4 w-4 text-muted-foreground" />
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" class="w-56">
                            <DropdownMenuLabel>
                                <div class="text-sm font-medium">
                                    {{ user.name }}
                                </div>
                                <div class="truncate text-xs font-normal text-muted-foreground">
                                    {{ user.email }}
                                </div>
                            </DropdownMenuLabel>

                            <DropdownMenuSeparator />

                            <DropdownMenuItem as-child>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="w-full"
                                    @click="logoutTokenClean"
                                >
                                    <LogOut class="mr-2 h-4 w-4" />
                                    Выйти
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <!-- Page heading -->
            <div
                v-if="$slots.header"
                class="border-b border-slate-200 bg-white"
            >
                <div class="px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </div>

            <!-- Page content -->
            <main class="min-h-[calc(100vh-4rem)] px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
