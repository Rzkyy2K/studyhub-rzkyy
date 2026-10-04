<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Calendar,
    CheckCircle2,
    Clock,
    Edit3,
    GraduationCap,
    Trash2,
    X,
} from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import DeleteConfirmModal from '@/components/DeleteConfirmModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Task } from '@/types';

const props = defineProps<{
    open: boolean;
    task: Task | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'edit', task: Task): void;
    (e: 'deleted'): void;
}>();

const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);

const toggleComplete = () => {
    if (!props.task) return;
    router.patch(`/tasks/${props.task.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            const isCompleted = props.task?.status === 'completed';
            toast.success(isCompleted ? 'Status diubah ke belum selesai' : 'Tugas ditandai selesai! 🎉');
            emit('update:open', false);
        },
    });
};

const openDeleteConfirm = () => {
    if (!props.task) return;
    isDeleteModalOpen.value = true;
};

const handleConfirmDelete = () => {
    if (!props.task) return;
    isDeleting.value = true;
    router.delete(`/tasks/${props.task.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Tugas berhasil dihapus.');
            isDeleteModalOpen.value = false;
            emit('update:open', false);
            emit('deleted');
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const handleEdit = () => {
    if (!props.task) return;
    const taskToEdit = { ...props.task };
    emit('update:open', false);
    emit('edit', taskToEdit);
};

// Priority badge color
const priorityClass = (p?: string) => {
    switch (p) {
        case 'high':
            return 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800';
        case 'medium':
            return 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800';
        default:
            return 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
};

const priorityLabel = (p?: string) => {
    switch (p) {
        case 'high':
            return '🔴 High';
        case 'medium':
            return '🟡 Medium';
        default:
            return '🟢 Low';
    }
};

// Status badge
const statusClass = (s?: string) => {
    switch (s) {
        case 'completed':
            return 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800';
        case 'in_progress':
            return 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800';
        default:
            return 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
};

const statusLabel = (s?: string) => {
    switch (s) {
        case 'completed':
            return 'Selesai';
        case 'in_progress':
            return 'Sedang Dikerjakan';
        default:
            return 'Belum Dikerjakan';
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent v-if="task" class="sm:max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl">
            <DialogHeader class="space-y-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="outline" :class="priorityClass(task.priority)">
                        {{ priorityLabel(task.priority) }}
                    </Badge>
                    <Badge variant="outline" :class="statusClass(task.status)">
                        {{ statusLabel(task.status) }}
                    </Badge>

                    <!-- Urgency Warning Badge if applicable -->
                    <span
                        v-if="task.urgency === 'overdue' && task.status !== 'completed'"
                        class="inline-flex items-center gap-1 rounded-full bg-rose-100 dark:bg-rose-950/60 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800"
                    >
                        <AlertCircle class="size-3" />
                        Terlambat
                    </span>
                    <span
                        v-else-if="task.urgency === 'danger' && task.status !== 'completed'"
                        class="inline-flex items-center gap-1 rounded-full bg-rose-50 dark:bg-rose-950/40 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800"
                    >
                        <Clock class="size-3" />
                        {{ task.urgency_label }}
                    </span>
                    <span
                        v-else-if="task.urgency === 'warning' && task.status !== 'completed'"
                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-950/40 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800"
                    >
                        <Clock class="size-3" />
                        {{ task.urgency_label }}
                    </span>
                </div>

                <DialogTitle class="text-xl font-bold text-slate-900 dark:text-white leading-snug">
                    {{ task.title }}
                </DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-3 text-sm">
                <!-- Subject -->
                <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                    <GraduationCap class="size-4.5 text-indigo-600 dark:text-indigo-400 shrink-0" />
                    <span class="font-medium">Mata Pelajaran:</span>
                    <span class="text-slate-900 dark:text-white font-semibold">
                        {{ task.subject ? task.subject.name : 'Umum' }}
                    </span>
                    <span v-if="task.subject?.teacher" class="text-xs text-slate-500 dark:text-slate-400">
                        ({{ task.subject.teacher }})
                    </span>
                </div>

                <!-- Deadline -->
                <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                    <Calendar class="size-4.5 text-indigo-600 dark:text-indigo-400 shrink-0" />
                    <span class="font-medium">Deadline:</span>
                    <span class="text-slate-900 dark:text-white font-semibold">
                        {{ task.formatted_deadline || task.deadline }}
                    </span>
                </div>

                <!-- Description -->
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3.5 border border-slate-100 dark:border-slate-800">
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1 uppercase tracking-wider">
                        Deskripsi
                    </div>
                    <p v-if="task.description" class="text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed text-sm">
                        {{ task.description }}
                    </p>
                    <p v-else class="text-slate-400 dark:text-slate-500 italic text-xs">
                        Tidak ada deskripsi tambahan.
                    </p>
                </div>

                <!-- Meta info -->
                <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-400 dark:text-slate-500 pt-1 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <span>Dibuat: </span>
                        <span>{{ new Date(task.created_at || '').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                    </div>
                    <div class="text-right">
                        <span>Diperbarui: </span>
                        <span>{{ new Date(task.updated_at || '').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                    </div>
                </div>
            </div>

            <DialogFooter class="flex sm:justify-between items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="openDeleteConfirm"
                    class="text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 hover:text-rose-700 dark:hover:text-rose-300 text-xs px-2.5"
                >
                    <Trash2 class="mr-1.5 size-4" />
                    Hapus
                </Button>

                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="handleEdit"
                        class="text-xs border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                    >
                        <Edit3 class="mr-1.5 size-4" />
                        Edit
                    </Button>

                    <Button
                        type="button"
                        size="sm"
                        @click="toggleComplete"
                        class="text-xs font-medium"
                        :class="task.status === 'completed' ? 'bg-slate-600 hover:bg-slate-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
                    >
                        <CheckCircle2 class="mr-1.5 size-4" />
                        {{ task.status === 'completed' ? 'Tandai Belum Selesai' : 'Tandai Selesai' }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Modal -->
    <DeleteConfirmModal
        :open="isDeleteModalOpen"
        @update:open="isDeleteModalOpen = $event"
        title="Hapus Tugas"
        :item-name="task?.title"
        description="Apakah Anda yakin ingin menghapus tugas ini secara permanen?"
        confirm-text="Ya, Hapus Tugas"
        :loading="isDeleting"
        @confirm="handleConfirmDelete"
    />
</template>
