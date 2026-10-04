<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarDays,
    CheckSquare,
    LayoutGrid,
    ListTodo,
    Settings,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useSchoolSession } from '@/composables/useSchoolSession';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';
import type { NavItem } from '@/types';

const { isTeori } = useSchoolSession();
const { isMobile, setOpenMobile } = useSidebar();

// Close sidebar on mobile on any page navigation
router.on('navigate', () => {
    if (isMobile.value) {
        setOpenMobile(false);
    }
});

const handleLogoClick = () => {
    if (isMobile.value) {
        setOpenMobile(false);
    }
};

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Tugas',
        href: '/tasks',
        icon: CheckSquare,
    },
    {
        title: 'Jadwal',
        href: '/schedule',
        icon: CalendarDays,
    },
    {
        title: 'To-Do',
        href: '/todos',
        icon: ListTodo,
    },
    {
        title: 'Mata Pelajaran',
        href: '/subjects',
        icon: BookOpen,
    },
    {
        title: 'Pengaturan',
        href: edit(),
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="sidebar"
        class="transition-colors duration-500 shadow-xs"
        :class="[
            isTeori
                ? 'border-r border-indigo-200/70 dark:border-indigo-950/70 bg-gradient-to-b from-[#dbe7f7]/95 via-[#e5eef9]/90 to-[#dce8f7]/95 dark:from-[#081224]/95 dark:via-[#060c18]/95 dark:to-[#0a1529]/95 backdrop-blur-xl'
                : 'border-r border-amber-200/70 dark:border-amber-950/70 bg-gradient-to-b from-[#ede1cf]/95 via-[#f5ebdd]/90 to-[#eee0ce]/95 dark:from-[#171109]/95 dark:via-[#0f0b07]/95 dark:to-[#1a140c]/95 backdrop-blur-xl'
        ]"
    >
        <SidebarHeader
            class="px-4 py-3 transition-colors"
            :class="isTeori ? 'border-b border-indigo-200/60 dark:border-indigo-950/70' : 'border-b border-amber-200/60 dark:border-amber-950/70'"
        >
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="hover:bg-transparent">
                        <Link :href="dashboard()" @click="handleLogoClick">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="py-2">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter
            class="p-2 transition-colors"
            :class="isTeori ? 'border-t border-indigo-200/60 dark:border-indigo-950/70' : 'border-t border-amber-200/60 dark:border-amber-950/70'"
        >
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
