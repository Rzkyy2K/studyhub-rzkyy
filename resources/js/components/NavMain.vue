<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useSchoolSession } from '@/composables/useSchoolSession';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
const { isTeori } = useSchoolSession();
const { isMobile, setOpenMobile } = useSidebar();

const handleNavClick = () => {
    if (isMobile.value) {
        setOpenMobile(false);
    }
};
</script>

<template>
    <SidebarGroup class="px-2 py-2">
        <SidebarMenu class="gap-1.5">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    class="h-10 rounded-lg text-sm font-medium transition-colors"
                    :class="[
                        isCurrentUrl(item.href)
                            ? isTeori
                                ? '!bg-indigo-600/15 dark:!bg-indigo-500/25 !text-indigo-800 dark:!text-indigo-200 border border-indigo-400/40 dark:border-indigo-500/40 font-semibold shadow-xs'
                                : '!bg-amber-600/15 dark:!bg-amber-500/25 !text-amber-900 dark:!text-amber-200 border border-amber-400/40 dark:border-amber-500/40 font-semibold shadow-xs'
                            : isTeori
                                ? 'text-slate-700 dark:text-slate-300 hover:bg-indigo-100/60 dark:hover:bg-indigo-950/40 hover:text-indigo-950 dark:hover:text-white'
                                : 'text-slate-700 dark:text-slate-300 hover:bg-amber-100/60 dark:hover:bg-amber-950/40 hover:text-amber-950 dark:hover:text-white'
                    ]"
                >
                    <Link
                        :href="item.href"
                        @click="handleNavClick"
                        class="flex items-center gap-3 px-3"
                    >
                        <component
                            :is="item.icon"
                            class="size-5 shrink-0 transition-colors"
                            :class="isCurrentUrl(item.href) ? (isTeori ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600 dark:text-amber-400') : 'text-slate-500 dark:text-slate-400'"
                        />
                        <span class="truncate">{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
