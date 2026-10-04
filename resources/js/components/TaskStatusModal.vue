<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Check,
    CheckCircle2,
    Clock,
    Edit3,
    GraduationCap,
    Sparkles,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Task, TaskStatus } from '@/types';

const props = defineProps<{
    open: boolean;
    task: Task | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'edit-full', task: Task): void;
}>();

const selectedStatus = ref<TaskStatus>('pending');
const isSubmitting = ref(false);

watch(
    () => props.task,
    (newTask) => {
        if (newTask) {
            selectedStatus.value = newTask.status;
        }
    },
    { immediate: true },
);

const saveStatus = () => {
    if (!props.task) return;

    if (selectedStatus.value === props.task.status) {
        emit('update:open', false);
        return;
    }

    isSubmitting.value = true;
    router.patch(
        `/tasks/${props.task.id}/status`,
        { status: selectedStatus.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:open', false);
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};

const handleOpenFullEdit = () => {
    if (!props.task) return;
    emit('update:open', false);
    emit('edit-full', props.task);
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl">
            <DialogHeader class="space-y-1 text-left">
                <DialogTitle class="text-lg font-bold text-slate-900 dark:text-white">
                    Update Status Tugas
                </DialogTitle>
                <div v-if="task" class="text-xs text-slate-500 dark:text-slate-400 space-y-1 pt-1">
                    <p class="font-semibold text-slate-800 dark:text-slate-200 line-clamp-1">
                        {{ task.title }}
                    </p>
                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                        <GraduationCap class="size-3 text-indigo-500" />
                        <span>{{ task.subject ? task.subject.name : 'Umum' }}</span>
                    </div>
                </div>
            </DialogHeader>

            <div class="space-y-2.5 py-4">
                <!-- Option 1: Belum Dikerjakan -->
                <button
                    type="button"
                    @click="selectedStatus = 'pending'"
                    class="w-full flex items-center justify-between p-3.5 rounded-xl border transition-all text-left cursor-pointer"
                    :class="selectedStatus === 'pending'
                        ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 text-slate-900 dark:text-white ring-1 ring-indigo-600'
                        : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900'"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 shrink-0">
                            <Clock class="size-4.5" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                                Belum Dikerjakan
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Tugas belum mulai dikerjakan
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="selectedStatus === 'pending'"
                        class="flex size-5 items-center justify-center rounded-full bg-indigo-600 text-white shrink-0"
                    >
                        <Check class="size-3" />
                    </div>
                </button>

                <!-- Option 2: Sedang Dikerjakan -->
                <button
                    type="button"
                    @click="selectedStatus = 'in_progress'"
                    class="w-full flex items-center justify-between p-3.5 rounded-xl border transition-all text-left cursor-pointer"
                    :class="selectedStatus === 'in_progress'
                        ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 text-slate-900 dark:text-white ring-1 ring-indigo-600'
                        : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900'"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-lg bg-indigo-100/70 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 shrink-0">
                            <Sparkles class="size-4.5" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                                Sedang Dikerjakan
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Sedang dalam proses pengerjaan
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="selectedStatus === 'in_progress'"
                        class="flex size-5 items-center justify-center rounded-full bg-indigo-600 text-white shrink-0"
                    >
                        <Check class="size-3" />
                    </div>
                </button>

                <!-- Option 3: Selesai -->
                <button
                    type="button"
                    @click="selectedStatus = 'completed'"
                    class="w-full flex items-center justify-between p-3.5 rounded-xl border transition-all text-left cursor-pointer"
                    :class="selectedStatus === 'completed'
                        ? 'border-emerald-600 bg-emerald-50/60 dark:bg-emerald-950/40 text-slate-900 dark:text-white ring-1 ring-emerald-600'
                        : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900'"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-100/70 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 shrink-0">
                            <CheckCircle2 class="size-4.5" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                                Selesai
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Pindahkan ke histori tugas selesai
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="selectedStatus === 'completed'"
                        class="flex size-5 items-center justify-center rounded-full bg-emerald-600 text-white shrink-0"
                    >
                        <Check class="size-3" />
                    </div>
                </button>
            </div>

            <!-- Full Edit Alternative Link -->
            <div class="pt-1 pb-2 text-center border-t border-slate-100 dark:border-slate-800">
                <button
                    type="button"
                    @click="handleOpenFullEdit"
                    class="text-xs text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                    <Edit3 class="size-3.5" />
                    <span>Edit rincian tugas lainnya (judul, deadline, dll.)</span>
                </button>
            </div>

            <DialogFooter class="flex items-center justify-end gap-2 pt-2">
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                    class="h-9 text-xs border-slate-200 dark:border-slate-700 cursor-pointer"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    @click="saveStatus"
                    :disabled="isSubmitting"
                    class="h-9 text-xs bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer px-4"
                >
                    {{ isSubmitting ? 'Menyimpan...' : 'Simpan Status' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
