<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BookOpen,
    Calendar,
    Check,
    CheckCircle2,
    Edit3,
    ListTodo,
    Plus,
    Sparkles,
    Trash2,
    Wrench,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import TodoFormModal from '@/components/TodoFormModal.vue';
import DeleteConfirmModal from '@/components/DeleteConfirmModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Todo } from '@/types';

defineOptions({
    layout: AppLayout,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'To-Do',
        href: '/todos',
    },
];

const props = defineProps<{
    todos: Todo[];
    active_session?: 'teori' | 'bengkel';
    session_filter?: string;
    stats: {
        total: number;
        completed: number;
        pending: number;
    };
}>();

const selectedSession = ref(props.session_filter || props.active_session || 'teori');

const switchSession = (sessionVal: string) => {
    selectedSession.value = sessionVal;
    router.get(
        '/todos',
        { session: sessionVal },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// Modals
const isFormModalOpen = ref(false);
const todoToEdit = ref<Todo | null>(null);

// Check if URL has ?create=true
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('create') === 'true') {
    isFormModalOpen.value = true;
}

// Progress Percentage
const progressPercentage = computed(() => {
    if (props.stats.total === 0) return 0;
    return Math.round((props.stats.completed / props.stats.total) * 100);
});

const openCreateModal = () => {
    todoToEdit.value = null;
    isFormModalOpen.value = true;
};

const openEditModal = (todo: Todo) => {
    todoToEdit.value = todo;
    isFormModalOpen.value = true;
};

const toggleTodo = (todo: Todo) => {
    router.patch(`/todos/${todo.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            const nextIsCompleted = todo.status !== 'completed';
            toast.success(nextIsCompleted ? 'Aktivitas diselesaikan! 🎉' : 'Aktivitas ditandai belum selesai.');
        },
    });
};

const isDeleteModalOpen = ref(false);
const todoToDelete = ref<Todo | null>(null);
const isDeletingTodo = ref(false);

const confirmDeleteTodo = (todo: Todo) => {
    todoToDelete.value = todo;
    isDeleteModalOpen.value = true;
};

const handleDeleteTodo = () => {
    if (!todoToDelete.value) return;
    isDeletingTodo.value = true;
    router.delete(`/todos/${todoToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('To-Do berhasil dihapus.');
            isDeleteModalOpen.value = false;
            todoToDelete.value = null;
        },
        onFinish: () => {
            isDeletingTodo.value = false;
        },
    });
};

const priorityColor = (p: string) => {
    switch (p) {
        case 'high':
            return 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900';
        case 'medium':
            return 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900';
        default:
            return 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700';
    }
};

const priorityText = (p: string) => {
    switch (p) {
        case 'high':
            return 'High';
        case 'medium':
            return 'Medium';
        default:
            return 'Low';
    }
};
</script>

<template>
    <Head title="To-Do - StudyHub" />

    <div class="space-y-6 w-full">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                    To-Do
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Atur aktivitas yang ingin kamu selesaikan.
                </p>
            </div>

                <Button
                    @click="openCreateModal"
                    class="h-9.5 gap-2 bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs font-medium text-xs sm:text-sm self-start sm:self-auto rounded-lg cursor-pointer"
                >
                    <Plus class="size-4" />
                    <span>Tambah To-Do</span>
                </Button>
        </div>

        <!-- Session Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 dark:border-slate-800 pb-3">
            <button
                type="button"
                @click="switchSession('teori')"
                :class="[
                    'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer',
                    selectedSession === 'teori'
                        ? 'bg-indigo-600 text-white shadow-xs ring-1 ring-indigo-600'
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                ]"
            >
                <BookOpen class="size-3.5" :class="selectedSession === 'teori' ? 'text-white' : 'text-indigo-500'" />
                <span>Sesi Teori</span>
                <span
                    v-if="active_session === 'teori'"
                    class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                    :class="selectedSession === 'teori' ? 'bg-indigo-700 text-indigo-100' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'"
                >
                    Aktif
                </span>
            </button>

            <button
                type="button"
                @click="switchSession('bengkel')"
                :class="[
                    'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer',
                    selectedSession === 'bengkel'
                        ? 'bg-amber-600 text-white shadow-xs ring-1 ring-amber-600'
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                ]"
            >
                <Wrench class="size-3.5" :class="selectedSession === 'bengkel' ? 'text-white' : 'text-amber-500'" />
                <span>Sesi Bengkel</span>
                <span
                    v-if="active_session === 'bengkel'"
                    class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                    :class="selectedSession === 'bengkel' ? 'bg-amber-700 text-amber-100' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'"
                >
                    Aktif
                </span>
            </button>

            <button
                type="button"
                @click="switchSession('all')"
                :class="[
                    'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer',
                    selectedSession === 'all'
                        ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 shadow-xs'
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                ]"
            >
                <span>Semua Sesi</span>
            </button>
        </div>

        <!-- Progress Card -->
        <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs transition-colors">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                    Progres Aktivitas
                </span>
                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                    {{ stats.completed }} dari {{ stats.total }} selesai ({{ progressPercentage }}%)
                </span>
            </div>

            <!-- Progress Bar -->
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div
                    class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-500 ease-out"
                    :style="{ width: `${progressPercentage}%` }"
                ></div>
            </div>
        </div>

        <!-- Todo List -->
        <div v-if="todos && todos.length > 0" class="space-y-2.5">
            <div
                v-for="todo in todos"
                :key="todo.id"
                class="group flex items-center justify-between gap-3.5 rounded-xl border bg-white dark:bg-slate-900 p-4 transition-all hover:border-slate-300 dark:hover:border-slate-700 hover:shadow-xs"
                :class="[
                    todo.status === 'completed'
                        ? 'border-slate-200/60 dark:border-slate-800/60 bg-slate-50/60 dark:bg-slate-900/40 opacity-75'
                        : 'border-slate-200/90 dark:border-slate-800'
                ]"
            >
                <!-- Left: Checkbox & Title -->
                <div class="flex items-center gap-3.5 min-w-0 flex-1">
                    <button
                        type="button"
                        @click="toggleTodo(todo)"
                        class="flex size-6 shrink-0 items-center justify-center rounded-lg border transition-all cursor-pointer"
                        :class="[
                            todo.status === 'completed'
                                ? 'bg-indigo-600 border-indigo-600 text-white'
                                : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500 bg-white dark:bg-slate-800'
                        ]"
                    >
                        <Check v-if="todo.status === 'completed'" class="size-4 stroke-[3]" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span
                                class="text-sm font-medium text-slate-800 dark:text-slate-100 break-words leading-snug"
                                :class="{ 'line-through text-slate-400 dark:text-slate-500': todo.status === 'completed' }"
                            >
                                {{ todo.title }}
                            </span>
                            <span
                                v-if="todo.session && todo.session !== 'all'"
                                class="px-2 py-0.2 rounded text-[10px] font-bold uppercase tracking-wider border"
                                :class="todo.session === 'bengkel'
                                    ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60'
                                    : 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60'"
                            >
                                {{ todo.session === 'bengkel' ? 'Bengkel' : 'Teori' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                            <span v-if="todo.formatted_due_date" class="inline-flex items-center gap-1">
                                <Calendar class="size-3" />
                                Target: {{ todo.formatted_due_date }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Priority Badge & Actions -->
                <div class="flex items-center gap-2 shrink-0">
                    <Badge
                        variant="outline"
                        class="text-[10px] font-medium py-0.5 px-2"
                        :class="priorityColor(todo.priority)"
                    >
                        {{ priorityText(todo.priority) }}
                    </Badge>

                    <button
                        type="button"
                        @click="openEditModal(todo)"
                        title="Edit to-do"
                        class="rounded-lg p-1.5 text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer"
                    >
                        <Edit3 class="size-4" />
                    </button>

                    <button
                        type="button"
                        @click="confirmDeleteTodo(todo)"
                        title="Hapus to-do"
                        class="rounded-lg p-1.5 text-slate-400 dark:text-slate-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 dark:hover:text-rose-400 transition-colors cursor-pointer"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-12 text-center space-y-4"
        >
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                <Sparkles class="size-7" />
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Semua aktivitas selesai 🎉
                </h3>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Tidak ada aktivitas To-Do yang tertunda. Tambahkan target baru untuk harimu.
                </p>
            </div>
            <Button
                @click="openCreateModal"
                class="h-9 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs px-4 cursor-pointer"
            >
                <Plus class="size-4 mr-1.5" />
                Tambah To-Do
            </Button>
        </div>

        <!-- Form Modal -->
        <TodoFormModal
            :open="isFormModalOpen"
            @update:open="isFormModalOpen = $event"
            :todo="todoToEdit"
        />

        <!-- Delete To-Do Modal -->
        <DeleteConfirmModal
            :open="isDeleteModalOpen"
            @update:open="isDeleteModalOpen = $event"
            title="Hapus To-Do"
            :item-name="todoToDelete?.title"
            description="Apakah Anda yakin ingin menghapus to-do ini?"
            confirm-text="Ya, Hapus To-Do"
            :loading="isDeletingTodo"
            @confirm="handleDeleteTodo"
        />
    </div>
</template>
