<script setup lang="ts">
import {
    CalendarDays,
    CheckSquare,
    ListTodo,
    X,
} from '@lucide/vue';

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'select-action', action: 'task' | 'schedule' | 'todo'): void;
}>();

const handleSelect = (action: 'task' | 'schedule' | 'todo') => {
    emit('update:open', false);
    emit('select-action', action);
};
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-0 transition-opacity"
        @click.self="emit('update:open', false)"
    >
        <div
            class="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 p-5 shadow-xl border border-slate-100 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-200"
        >
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100 text-base">Tambah Aktivitas</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pilih jenis aktivitas yang ingin dibuat</p>
                </div>
                <button
                    type="button"
                    @click="emit('update:open', false)"
                    class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300"
                >
                    <X class="size-5" />
                </button>
            </div>

            <div class="mt-4 flex flex-col gap-2.5">
                <!-- Tambah Tugas -->
                <button
                    type="button"
                    @click="handleSelect('task')"
                    class="flex items-center gap-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 p-3 text-left transition-all hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/30 group"
                >
                    <div class="flex size-10 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                        <CheckSquare class="size-5" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Tambah Tugas</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Tugas mata pelajaran dan deadline sekolah</div>
                    </div>
                </button>

                <!-- Tambah Jadwal -->
                <button
                    type="button"
                    @click="handleSelect('schedule')"
                    class="flex items-center gap-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 p-3 text-left transition-all hover:border-amber-300 dark:hover:border-amber-700 hover:bg-amber-50/50 dark:hover:bg-amber-950/30 group"
                >
                    <div class="flex size-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                        <CalendarDays class="size-5" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100 group-hover:text-amber-600 dark:group-hover:text-amber-400">Tambah Jadwal</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Jadwal pelajaran mingguan dan ruangan</div>
                    </div>
                </button>

                <!-- Tambah To-Do -->
                <button
                    type="button"
                    @click="handleSelect('todo')"
                    class="flex items-center gap-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 p-3 text-left transition-all hover:border-emerald-300 dark:hover:border-emerald-700 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30 group"
                >
                    <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <ListTodo class="size-5" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Tambah To-Do</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Checklist target kecil atau aktivitas pribadi</div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
