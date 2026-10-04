<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckSquare,
    Home,
    ListTodo,
    Plus,
    User,
} from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useSchoolSession } from '@/composables/useSchoolSession';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';

const emit = defineEmits<{
    (e: 'open-quick-action'): void;
}>();

const { isCurrentUrl } = useCurrentUrl();
const { isTeori } = useSchoolSession();
</script>

<template>
    <nav
        aria-label="Mobile Navigation"
        class="fixed bottom-0 left-0 right-0 z-40 flex h-16 items-center justify-around border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 px-2 backdrop-blur-md md:hidden shadow-lg transition-colors"
    >
        <!-- Home / Dashboard -->
        <Link
            :href="dashboard()"
            class="flex flex-1 flex-col items-center justify-center py-1 transition-colors"
            :class="isCurrentUrl(dashboard()) ? (isTeori ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-amber-600 dark:text-amber-400 font-semibold') : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
        >
            <Home class="size-5" />
            <span class="mt-0.5 text-[10px] tracking-tight">Home</span>
        </Link>

        <!-- Tugas -->
        <Link
            href="/tasks"
            class="flex flex-1 flex-col items-center justify-center py-1 transition-colors"
            :class="isCurrentUrl('/tasks') ? (isTeori ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-amber-600 dark:text-amber-400 font-semibold') : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
        >
            <CheckSquare class="size-5" />
            <span class="mt-0.5 text-[10px] tracking-tight">Tugas</span>
        </Link>

        <!-- Quick Action Button (+) -->
        <div class="flex flex-1 items-center justify-center -translate-y-2">
            <button
                type="button"
                @click="emit('open-quick-action')"
                aria-label="Tambah Aktivitas"
                class="flex size-11 items-center justify-center rounded-full text-white shadow-md transition-transform active:scale-95 cursor-pointer"
                :class="isTeori ? 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-300 dark:shadow-indigo-950/50' : 'bg-amber-600 hover:bg-amber-700 shadow-amber-300 dark:shadow-amber-950/50'"
            >
                <Plus class="size-6 stroke-[2.5]" />
            </button>
        </div>

        <!-- Jadwal -->
        <Link
            href="/schedule"
            class="flex flex-1 flex-col items-center justify-center py-1 transition-colors"
            :class="isCurrentUrl('/schedule') ? (isTeori ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-amber-600 dark:text-amber-400 font-semibold') : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
        >
            <CalendarDays class="size-5" />
            <span class="mt-0.5 text-[10px] tracking-tight">Jadwal</span>
        </Link>

        <!-- To-Do -->
        <Link
            href="/todos"
            class="flex flex-1 flex-col items-center justify-center py-1 transition-colors"
            :class="isCurrentUrl('/todos') ? (isTeori ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-amber-600 dark:text-amber-400 font-semibold') : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
        >
            <ListTodo class="size-5" />
            <span class="mt-0.5 text-[10px] tracking-tight">To-Do</span>
        </Link>

        <!-- Profil -->
        <Link
            :href="edit()"
            class="flex flex-1 flex-col items-center justify-center py-1 transition-colors"
            :class="isCurrentUrl(edit()) ? (isTeori ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-amber-600 dark:text-amber-400 font-semibold') : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
        >
            <User class="size-5" />
            <span class="mt-0.5 text-[10px] tracking-tight">Profil</span>
        </Link>
    </nav>
</template>
