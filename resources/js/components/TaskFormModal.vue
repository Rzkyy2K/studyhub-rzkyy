<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { BookOpen, Loader2, Wrench } from '@lucide/vue';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Subject, Task } from '@/types';

const props = defineProps<{
    open: boolean;
    task?: Task | null;
    subjects: Subject[];
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'saved'): void;
}>();

const page = usePage();
const currentActiveSession = computed<'teori' | 'bengkel'>(() => {
    return (page.props.school_session as any)?.current || 'teori';
});

const isEditing = computed(() => !!props.task);

const form = useForm({
    title: '',
    subject_id: '' as string | number,
    new_subject_name: '',
    description: '',
    deadline: '',
    priority: 'medium' as 'low' | 'medium' | 'high',
    status: 'pending' as 'pending' | 'in_progress' | 'completed',
    session: 'teori' as 'teori' | 'bengkel',
});

// Format date to YYYY-MM-DDTHH:mm for datetime-local input
const formatForInput = (dateStr?: string) => {
    if (!dateStr) {
        // default to today at 20:00
        const d = new Date();
        d.setHours(20, 0, 0, 0);
        return d.toISOString().slice(0, 16);
    }
    const d = new Date(dateStr);
    const offset = d.getTimezoneOffset() * 60000;
    const local = new Date(d.getTime() - offset);
    return local.toISOString().slice(0, 16);
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            if (props.task) {
                form.title = props.task.title;
                form.subject_id = props.task.subject_id || '';
                form.new_subject_name = '';
                form.description = props.task.description || '';
                form.deadline = formatForInput(props.task.deadline);
                form.priority = props.task.priority;
                form.status = props.task.status;
                form.session = (props.task.session as 'teori' | 'bengkel') || currentActiveSession.value;
            } else {
                form.reset();
                form.deadline = formatForInput();
                form.priority = 'medium';
                form.status = 'pending';
                form.session = currentActiveSession.value;
                form.subject_id = props.subjects[0]?.id || '';
            }
            form.clearErrors();
        }
    },
    { immediate: true },
);

watch(
    () => form.subject_id,
    (newSubId) => {
        if (newSubId && newSubId !== 'new') {
            const found = props.subjects.find((s) => s.id === Number(newSubId));
            if (found && (found as any).session) {
                form.session = (found as any).session;
            }
        }
    },
);

const save = () => {
    if (isEditing.value && props.task) {
        form.put(`/tasks/${props.task.id}`, {
            onSuccess: () => {
                toast.success('Tugas berhasil diperbarui.');
                emit('update:open', false);
                emit('saved');
            },
        });
    } else {
        form.post('/tasks', {
            onSuccess: () => {
                toast.success('Tugas berhasil ditambahkan.');
                emit('update:open', false);
                emit('saved');
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl">
            <DialogHeader class="space-y-1">
                <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">
                    {{ isEditing ? 'Edit Tugas' : 'Tambah Tugas Baru' }}
                </DialogTitle>
                <DialogDescription class="text-xs text-slate-500 dark:text-slate-400">
                    {{ isEditing ? 'Perbarui detail tugas dan tenggat waktu.' : 'Catat tugas sekolah agar tidak terlewat.' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="save" class="space-y-4 py-2">
                <!-- Sesi Sekolah (Teori / Bengkel) -->
                <div class="space-y-1.5">
                    <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Sesi Sekolah *</Label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="form.session = 'teori'"
                            :class="[
                                'flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-medium transition-all',
                                form.session === 'teori'
                                    ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-semibold ring-1 ring-indigo-600'
                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                            ]"
                        >
                            <BookOpen class="size-4 text-indigo-500" />
                            <span>Sesi Teori</span>
                        </button>
                        <button
                            type="button"
                            @click="form.session = 'bengkel'"
                            :class="[
                                'flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-medium transition-all',
                                form.session === 'bengkel'
                                    ? 'border-amber-600 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-semibold ring-1 ring-amber-600'
                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                            ]"
                        >
                            <Wrench class="size-4 text-amber-500" />
                            <span>Sesi Bengkel</span>
                        </button>
                    </div>
                </div>

                <!-- Judul -->
                <div class="space-y-1.5">
                    <Label for="task_title" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Judul Tugas *</Label>
                    <Input
                        id="task_title"
                        v-model="form.title"
                        required
                        placeholder="Contoh: Membuat CRUD Laravel"
                        class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                    />
                    <span v-if="form.errors.title" class="text-xs text-rose-500">{{ form.errors.title }}</span>
                </div>

                <!-- Mata Pelajaran -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <Label for="task_subject" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Mata Pelajaran</Label>
                    </div>
                    <select
                        id="task_subject"
                        v-model="form.subject_id"
                        class="w-full h-9.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600"
                    >
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        <option v-for="sub in subjects" :key="sub.id" :value="sub.id">
                            {{ sub.name }} {{ sub.teacher ? `(${sub.teacher})` : '' }}
                        </option>
                        <option value="new">+ Tambah Mapel Baru...</option>
                    </select>

                    <div v-if="form.subject_id === 'new'" class="pt-1.5">
                        <Input
                            v-model="form.new_subject_name"
                            placeholder="Ketik nama mata pelajaran baru..."
                            class="h-9 text-xs border-indigo-300 dark:border-indigo-700 bg-indigo-50/30 dark:bg-indigo-950/30 dark:text-slate-100"
                        />
                    </div>
                </div>

                <!-- Deadline -->
                <div class="space-y-1.5">
                    <Label for="task_deadline" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Deadline (Tanggal & Jam) *</Label>
                    <Input
                        id="task_deadline"
                        type="datetime-local"
                        v-model="form.deadline"
                        required
                        class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                    />
                    <span v-if="form.errors.deadline" class="text-xs text-rose-500">{{ form.errors.deadline }}</span>
                </div>

                <!-- Priority & Status Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Priority -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Prioritas</Label>
                        <select
                            v-model="form.priority"
                            class="w-full h-9.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600"
                        >
                            <option value="low">Low (Rendah)</option>
                            <option value="medium">Medium (Sedang)</option>
                            <option value="high">High (Tinggi)</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Status</Label>
                        <select
                            v-model="form.status"
                            class="w-full h-9.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600"
                        >
                            <option value="pending">Belum Dikerjakan</option>
                            <option value="in_progress">Sedang Dikerjakan</option>
                            <option value="completed">Selesai</option>
                        </select>
                    </div>
                </div>

                <!-- Deskripsi -->
                <div class="space-y-1.5">
                    <Label for="task_desc" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Deskripsi / Catatan (Opsional)</Label>
                    <textarea
                        id="task_desc"
                        v-model="form.description"
                        rows="3"
                        placeholder="Rincian tugas, link referensi, dll..."
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-sm text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600"
                    ></textarea>
                </div>

                <DialogFooter class="pt-2 sm:justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                        class="h-9.5 text-xs text-slate-600 dark:text-slate-400 dark:border-slate-700 dark:hover:bg-slate-800"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        class="h-9.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-medium"
                        :disabled="form.processing"
                    >
                        <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                        <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Tugas' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
