<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { BookOpen, Layers, Loader2, Wrench } from '@lucide/vue';
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
import type { Priority, Todo } from '@/types';

const props = defineProps<{
    open: boolean;
    todo?: Todo | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'saved'): void;
}>();

const page = usePage();
const currentActiveSession = computed<'teori' | 'bengkel'>(() => {
    return (page.props.school_session as any)?.current || 'teori';
});

const isEditing = computed(() => !!props.todo);

const form = useForm({
    title: '',
    priority: 'medium' as Priority,
    due_date: '',
    session: 'all' as 'teori' | 'bengkel' | 'all',
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            if (props.todo) {
                form.title = props.todo.title;
                form.priority = props.todo.priority;
                form.due_date = props.todo.due_date ? props.todo.due_date.slice(0, 10) : '';
                form.session = (props.todo.session as 'teori' | 'bengkel' | 'all') || 'all';
            } else {
                form.reset();
                form.priority = 'medium';
                form.session = currentActiveSession.value;
                // default to today's date
                form.due_date = new Date().toISOString().slice(0, 10);
            }
            form.clearErrors();
        }
    },
    { immediate: true },
);

const save = () => {
    if (isEditing.value && props.todo) {
        form.put(`/todos/${props.todo.id}`, {
            onSuccess: () => {
                toast.success('To-Do berhasil diperbarui.');
                emit('update:open', false);
                emit('saved');
            },
        });
    } else {
        form.post('/todos', {
            onSuccess: () => {
                toast.success('To-Do berhasil ditambahkan.');
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
                    {{ isEditing ? 'Edit To-Do' : 'Tambah To-Do Baru' }}
                </DialogTitle>
                <DialogDescription class="text-xs text-slate-500 dark:text-slate-400">
                    {{ isEditing ? 'Perbarui target aktivitas pribadimu.' : 'Buat checklist target kecil yang ingin diselesaikan.' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="save" class="space-y-4 py-2">
                <!-- Sesi Sekolah (Teori / Bengkel / Semua) -->
                <div class="space-y-1.5">
                    <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Sesi Terkait</Label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            @click="form.session = 'teori'"
                            :class="[
                                'flex items-center justify-center gap-1.5 p-2 rounded-xl border text-xs font-medium transition-all',
                                form.session === 'teori'
                                    ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-semibold ring-1 ring-indigo-600'
                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                            ]"
                        >
                            <BookOpen class="size-3.5 text-indigo-500" />
                            <span>Teori</span>
                        </button>
                        <button
                            type="button"
                            @click="form.session = 'bengkel'"
                            :class="[
                                'flex items-center justify-center gap-1.5 p-2 rounded-xl border text-xs font-medium transition-all',
                                form.session === 'bengkel'
                                    ? 'border-amber-600 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-semibold ring-1 ring-amber-600'
                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                            ]"
                        >
                            <Wrench class="size-3.5 text-amber-500" />
                            <span>Bengkel</span>
                        </button>
                        <button
                            type="button"
                            @click="form.session = 'all'"
                            :class="[
                                'flex items-center justify-center gap-1.5 p-2 rounded-xl border text-xs font-medium transition-all',
                                form.session === 'all'
                                    ? 'border-slate-900 dark:border-slate-100 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold ring-1 ring-slate-900 dark:ring-slate-100'
                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                            ]"
                        >
                            <Layers class="size-3.5 text-slate-500" />
                            <span>Semua</span>
                        </button>
                    </div>
                </div>

                <!-- Judul -->
                <div class="space-y-1.5">
                    <Label for="todo_title" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Aktivitas / Target *</Label>
                    <Input
                        id="todo_title"
                        v-model="form.title"
                        required
                        placeholder="Contoh: Belajar konsep Vue 3 reactivity"
                        class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                    />
                    <span v-if="form.errors.title" class="text-xs text-rose-500">{{ form.errors.title }}</span>
                </div>

                <!-- Priority & Due Date Grid -->
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

                    <!-- Due date -->
                    <div class="space-y-1.5">
                        <Label for="todo_due" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Target Tanggal</Label>
                        <Input
                            id="todo_due"
                            type="date"
                            v-model="form.due_date"
                            class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                        />
                    </div>
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
                        <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah To-Do' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
