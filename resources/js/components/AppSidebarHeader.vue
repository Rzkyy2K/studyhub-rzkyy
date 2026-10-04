<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import SessionSwitcher from '@/components/SessionSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useSchoolSession } from '@/composables/useSchoolSession';
import { edit } from '@/routes/profile';
import type { BreadcrumbItem, User } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const { isTeori } = useSchoolSession();
const user = computed<User>(() => page.props.auth?.user as User);
const initials = computed(() => {
    if (!user.value?.name) return 'U';
    return user.value.name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

const canGoBack = computed(() => {
    const path = page.url.split('?')[0];
    return path !== '/dashboard' && path !== '/';
});

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/dashboard');
    }
};
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-14 shrink-0 items-center justify-between border-b px-4 backdrop-blur-md transition-colors md:px-6"
        :class="[
            isTeori
                ? 'border-indigo-200/60 dark:border-indigo-950/70 bg-[#dbe7f7]/70 dark:bg-[#081224]/80'
                : 'border-amber-200/60 dark:border-amber-950/70 bg-[#ede0ce]/70 dark:bg-[#15100a]/80'
        ]"
    >
        <div class="flex items-center gap-2">
            <!-- Mobile Back Button -->
            <button
                v-if="canGoBack"
                type="button"
                @click="goBack"
                class="flex md:hidden size-8 items-center justify-center rounded-lg border border-slate-200/80 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-800 active:scale-95 transition-all shadow-2xs cursor-pointer shrink-0"
                title="Kembali ke halaman sebelumnya"
                aria-label="Kembali"
            >
                <ArrowLeft class="size-4" />
            </button>
            <SidebarTrigger class="-ml-1 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-2.5 sm:gap-3">
            <!-- School Session Switcher (Teori & Bengkel) -->
            <SessionSwitcher />

            <!-- Theme Toggle (Mode Terang & Gelap) -->
            <ThemeToggle />

            <!-- Notification Dropdown -->
            <NotificationDropdown />

            <!-- Profile Avatar -->
            <Link
                :href="edit()"
                class="flex items-center gap-2 rounded-full p-0.5 transition-all"
                :class="isTeori ? 'hover:ring-2 hover:ring-indigo-200 dark:hover:ring-indigo-800' : 'hover:ring-2 hover:ring-amber-200 dark:hover:ring-amber-800'"
                :title="user?.name"
            >
                <Avatar
                    class="size-8 text-xs font-semibold transition-colors"
                    :class="[
                        isTeori
                            ? 'border border-indigo-200 dark:border-indigo-900 bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300'
                            : 'border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300'
                    ]"
                >
                    <AvatarImage :src="user?.avatar || ''" :alt="user?.name" />
                    <AvatarFallback>{{ initials }}</AvatarFallback>
                </Avatar>
            </Link>
        </div>
    </header>
</template>
