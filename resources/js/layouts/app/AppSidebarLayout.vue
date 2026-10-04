<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import MobileBottomNav from '@/components/MobileBottomNav.vue';
import QuickActionModal from '@/components/QuickActionModal.vue';
import SessionBackground from '@/components/SessionBackground.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem, SchoolSessionInfo } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const sessionInfo = computed<SchoolSessionInfo | undefined>(
    () => page.props.school_session as SchoolSessionInfo | undefined
);
const currentSession = computed(() => sessionInfo.value?.view_session || 'teori');

// Set data-session on html root for global session styling
watchEffect(() => {
    if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('data-session', currentSession.value);
    }
});

const isQuickActionOpen = ref(false);

const handleQuickAction = (action: 'task' | 'schedule' | 'todo') => {
    if (action === 'task') {
        router.visit('/tasks?create=true');
    } else if (action === 'schedule') {
        router.visit('/schedule?create=true');
    } else if (action === 'todo') {
        router.visit('/todos?create=true');
    }
};
</script>

<template>
    <AppShell
        variant="sidebar"
        class="relative min-h-screen text-slate-800 dark:text-slate-100 antialiased transition-colors duration-500"
        :class="[
            currentSession === 'bengkel'
                ? 'bg-[#ede0ce] dark:bg-[#110e0a]'
                : 'bg-[#dbe7f7] dark:bg-[#060c19]'
        ]"
    >
        <!-- Scholify-Inspired Dynamic Session Ambient Background -->
        <SessionBackground :session="currentSession" />

        <AppSidebar class="relative z-20" />
        <AppContent variant="sidebar" class="relative z-10 min-w-0 flex-1 overflow-x-clip pb-20 md:pb-8 bg-transparent">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="p-4 sm:p-6 lg:p-8 w-full">
                <slot />
            </div>
        </AppContent>

        <!-- Mobile Bottom Navigation -->
        <MobileBottomNav @open-quick-action="isQuickActionOpen = true" />

        <!-- Global Quick Action Modal -->
        <QuickActionModal
            :open="isQuickActionOpen"
            @update:open="isQuickActionOpen = $event"
            @select-action="handleQuickAction"
        />

        <Toaster />
    </AppShell>
</template>
