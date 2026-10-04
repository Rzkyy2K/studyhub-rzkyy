<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    BookOpen,
    Calendar,
    CheckCircle2,
    Clock,
    Edit3,
    GraduationCap,
    History,
    Plus,
    Search,
    Trash2,
    Wrench,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import TaskDetailModal from '@/components/TaskDetailModal.vue';
import TaskFormModal from '@/components/TaskFormModal.vue';
import TaskStatusModal from '@/components/TaskStatusModal.vue';
import DeleteConfirmModal from '@/components/DeleteConfirmModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSchoolSession } from '@/composables/useSchoolSession';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Subject, Task } from '@/types';

defineOptions({
    layout: AppLayout,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tugas',
        href: '/tasks',
    },
];

const props = defineProps<{
    tasks: Task[];
    subjects: Subject[];
    active_session?: 'teori' | 'bengkel';
    filters: {
        tab: string;
        search: string;
        subject_id: string;
        priority: string;
        sort: string;
        session?: string;
    };
    counts: {
        all: number;
        pending: number;
        in_progress: number;
        completed: number;
        overdue: number;
    };
}>();

// Search & Filter State
const searchInput = ref(props.filters.search || '');
const selectedSubject = ref(props.filters.subject_id || 'all');
const selectedPriority = ref(props.filters.priority || 'all');
const selectedSort = ref(props.filters.sort || 'deadline_asc');
const activeTab = ref(props.filters.tab || 'all');
const selectedSession = ref(props.filters.session || props.active_session || 'teori');
const { isBengkel: isBengkelGlobal } = useSchoolSession();
const isBengkelTheme = computed(() => selectedSession.value === 'bengkel' || (selectedSession.value === 'all' && (props.active_session === 'bengkel' || isBengkelGlobal.value)));

// Modals
const isFormModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const isStatusModalOpen = ref(false);
const taskToEdit = ref<Task | null>(null);
const selectedTaskForDetail = ref<Task | null>(null);
const selectedTaskForStatus = ref<Task | null>(null);

// Check if URL has ?create=true
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('create') === 'true') {
    isFormModalOpen.value = true;
}

const applyFilters = () => {
    router.get(
        '/tasks',
        {
            tab: activeTab.value,
            session: selectedSession.value,
            search: searchInput.value || undefined,
            subject_id: selectedSubject.value !== 'all' ? selectedSubject.value : undefined,
            priority: selectedPriority.value !== 'all' ? selectedPriority.value : undefined,
            sort: selectedSort.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const switchSession = (sessionVal: string) => {
    selectedSession.value = sessionVal;
    applyFilters();
};

const setTab = (tab: string) => {
    activeTab.value = tab;
    applyFilters();
};

const openCreateModal = () => {
    taskToEdit.value = null;
    isFormModalOpen.value = true;
};

const openStatusModal = (task: Task) => {
    selectedTaskForStatus.value = task;
    isStatusModalOpen.value = true;
};

const openEditModal = (task: Task) => {
    taskToEdit.value = task;
    isFormModalOpen.value = true;
};

const openDetail = (task: Task) => {
    selectedTaskForDetail.value = task;
    isDetailModalOpen.value = true;
};

const toggleComplete = (task: Task, event: Event) => {
    event.stopPropagation();
    router.patch(`/tasks/${task.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Tugas selesai dan dipindahkan ke histori selesai! 🎉');
        },
    });
};

const isDeleteModalOpen = ref(false);
const taskToDelete = ref<Task | null>(null);
const isDeletingTask = ref(false);

const confirmDeleteTask = (task: Task, event: Event) => {
    event.stopPropagation();
    taskToDelete.value = task;
    isDeleteModalOpen.value = true;
};

const handleDeleteTask = () => {
    if (!taskToDelete.value) return;
    isDeletingTask.value = true;
    router.delete(`/tasks/${taskToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Tugas berhasil dihapus.');
            isDeleteModalOpen.value = false;
            taskToDelete.value = null;
        },
        onFinish: () => {
            isDeletingTask.value = false;
        },
    });
};

// Urgency styling helper
const getUrgencyBadge = (task: Task) => {
    if (task.status === 'completed') {
        return {
            text: 'Selesai',
            class: 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
            dot: 'bg-emerald-500',
        };
    }
    if (task.urgency === 'overdue') {
        return {
            text: 'Terlambat',
            class: 'bg-rose-100 dark:bg-rose-950/70 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900',
            dot: 'bg-rose-500',
        };
    }
    if (task.urgency === 'danger') {
        return {
            text: task.urgency_label || 'Segera Berakhir',
            class: 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900',
            dot: 'bg-rose-500',
        };
    }
    if (task.urgency === 'warning') {
        return {
            text: task.urgency_label || 'Besok',
            class: 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900',
            dot: 'bg-amber-500',
        };
    }
    return null;
};

// Priority badge
const priorityBadge = (priority: string) => {
    if (priority === 'high') {
        return { label: 'High', color: 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900' };
    }
    if (priority === 'medium') {
        return { label: 'Medium', color: 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900' };
    }
    return { label: 'Low', color: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700' };
};

// Status badge
const statusBadge = (status: string) => {
    if (status === 'completed') {
        return { label: 'Selesai', color: 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' };
    }
    if (status === 'in_progress') {
        return { label: 'Sedang Dikerjakan', color: 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800' };
    }
    return { label: 'Belum Dikerjakan', color: 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700' };
};
</script>

<template>
    <Head title="Tugas - StudyHub" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Tugas
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Kelola tugas aktif dan deadline kamu.
                </p>
            </div>

            <div class="flex items-center gap-2.5 self-start sm:self-auto flex-wrap">
                <!-- Button to Completed Tasks History -->
                <Link
                    href="/tasks/history"
                    class="h-9.5 gap-2 border border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 inline-flex items-center px-3.5 rounded-lg text-xs sm:text-sm font-semibold transition-all shadow-2xs"
                >
                    <History class="size-4 text-emerald-600 dark:text-emerald-400" />
                    <span>Histori Tugas Selesai</span>
                    <span
                        v-if="counts.completed > 0"
                        class="px-2 py-0.5 rounded-full text-[11px] bg-emerald-200/80 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 font-bold"
                    >
                        {{ counts.completed }}
                    </span>
                </Link>

                <Button
                    @click="openCreateModal"
                    class="h-9.5 gap-2 text-white shadow-xs font-medium text-xs sm:text-sm rounded-lg cursor-pointer transition-all"
                    :class="isBengkelTheme
                        ? 'bg-amber-600 hover:bg-amber-700 hover:shadow-amber-500/20'
                        : 'bg-indigo-600 hover:bg-indigo-700 hover:shadow-indigo-500/20'"
                >
                    <Plus class="size-4" />
                    <span>Tambah Tugas</span>
                </Button>
            </div>
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
                        : 'bg-white/75 dark:bg-slate-900/75 backdrop-blur-md text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800/80 hover:bg-white/90 dark:hover:bg-slate-800/90'
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
                        : 'bg-white/75 dark:bg-slate-900/75 backdrop-blur-md text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800/80 hover:bg-white/90 dark:hover:bg-slate-800/90'
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
                        : 'bg-white/75 dark:bg-slate-900/75 backdrop-blur-md text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800/80 hover:bg-white/90 dark:hover:bg-slate-800/90'
                ]"
            >
                <span>Semua Sesi</span>
            </button>
        </div>

        <!-- Filter Tabs (Semua Tugas Aktif, Belum Dikerjakan, Sedang Dikerjakan, Terlambat) -->
        <div class="border-b border-slate-200 dark:border-slate-800">
            <nav class="flex space-x-2 sm:space-x-4 overflow-x-auto pb-px" aria-label="Tabs">
                <button
                    type="button"
                    @click="setTab('all')"
                    class="group inline-flex items-center gap-2 border-b-2 py-3 px-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap cursor-pointer"
                    :class="activeTab === 'all'
                        ? (isBengkelTheme
                            ? 'border-amber-600 dark:border-amber-400 text-amber-600 dark:text-amber-400 font-semibold'
                            : 'border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 font-semibold')
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700 hover:text-slate-700 dark:hover:text-slate-200'"
                >
                    Semua Aktif
                    <span
                        class="rounded-full px-2 py-0.5 text-[11px]"
                        :class="activeTab === 'all'
                            ? (isBengkelTheme
                                ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400'
                                : 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400')
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                    >
                        {{ counts.all }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setTab('pending')"
                    class="group inline-flex items-center gap-2 border-b-2 py-3 px-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap cursor-pointer"
                    :class="activeTab === 'pending'
                        ? (isBengkelTheme
                            ? 'border-amber-600 dark:border-amber-400 text-amber-600 dark:text-amber-400 font-semibold'
                            : 'border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 font-semibold')
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700 hover:text-slate-700 dark:hover:text-slate-200'"
                >
                    Belum Dikerjakan
                    <span
                        class="rounded-full px-2 py-0.5 text-[11px]"
                        :class="activeTab === 'pending'
                            ? (isBengkelTheme
                                ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400'
                                : 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400')
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                    >
                        {{ counts.pending }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setTab('in_progress')"
                    class="group inline-flex items-center gap-2 border-b-2 py-3 px-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap cursor-pointer"
                    :class="activeTab === 'in_progress'
                        ? (isBengkelTheme
                            ? 'border-amber-600 dark:border-amber-400 text-amber-600 dark:text-amber-400 font-semibold'
                            : 'border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 font-semibold')
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700 hover:text-slate-700 dark:hover:text-slate-200'"
                >
                    Sedang Dikerjakan
                    <span
                        class="rounded-full px-2 py-0.5 text-[11px]"
                        :class="activeTab === 'in_progress'
                            ? (isBengkelTheme
                                ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400'
                                : 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400')
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                    >
                        {{ counts.in_progress }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setTab('overdue')"
                    class="group inline-flex items-center gap-2 border-b-2 py-3 px-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap cursor-pointer"
                    :class="activeTab === 'overdue' ? 'border-rose-600 dark:border-rose-400 text-rose-600 dark:text-rose-400 font-semibold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700 hover:text-slate-700 dark:hover:text-slate-200'"
                >
                    Terlambat
                    <span
                        class="rounded-full px-2 py-0.5 text-[11px]"
                        :class="activeTab === 'overdue' ? 'bg-rose-100 dark:bg-rose-950/70 text-rose-700 dark:text-rose-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                    >
                        {{ counts.overdue }}
                    </span>
                </button>

                <Link
                    href="/tasks/history"
                    class="group inline-flex items-center gap-1.5 border-b-2 border-transparent py-3 px-3 text-xs sm:text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors whitespace-nowrap"
                >
                    <History class="size-3.5" />
                    <span>Histori Selesai</span>
                    <span
                        class="rounded-full px-2 py-0.5 text-[11px] bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-semibold"
                    >
                        {{ counts.completed }}
                    </span>
                </Link>
            </nav>
        </div>

        <!-- Filter Controls Bar: Search, Subject, Priority, Sort -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <!-- Search bar -->
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-slate-400 dark:text-slate-500" />
                <Input
                    type="text"
                    v-model="searchInput"
                    @keyup.enter="applyFilters"
                    @blur="applyFilters"
                    placeholder="Cari berdasarkan judul atau deskripsi..."
                    class="h-9 pl-9 pr-3 text-xs border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-indigo-600"
                />
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <!-- Pelajaran filter -->
                <select
                    v-model="selectedSubject"
                    @change="applyFilters"
                    class="h-9 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 px-2.5 text-xs text-slate-700 dark:text-slate-200 focus:border-indigo-600 focus:outline-none"
                >
                    <option value="all">Semua Mapel</option>
                    <option v-for="sub in subjects" :key="sub.id" :value="sub.id">
                        {{ sub.name }}
                    </option>
                </select>

                <!-- Prioritas filter -->
                <select
                    v-model="selectedPriority"
                    @change="applyFilters"
                    class="h-9 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 px-2.5 text-xs text-slate-700 dark:text-slate-200 focus:border-indigo-600 focus:outline-none"
                >
                    <option value="all">Semua Prioritas</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>

                <!-- Deadline sort -->
                <select
                    v-model="selectedSort"
                    @change="applyFilters"
                    class="h-9 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 px-2.5 text-xs text-slate-700 dark:text-slate-200 focus:border-indigo-600 focus:outline-none"
                >
                    <option value="deadline_asc">Deadline Terdekat</option>
                    <option value="deadline_desc">Deadline Terjauh</option>
                </select>
            </div>
        </div>

        <!-- Task Cards Grid (Active Tasks) -->
        <div v-if="tasks && tasks.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="task in tasks"
                :key="task.id"
                @click="openDetail(task)"
                class="group relative flex flex-col justify-between rounded-xl border bg-white/75 dark:bg-slate-900/75 backdrop-blur-md p-5 transition-all hover:shadow-md cursor-pointer shadow-2xs"
                :class="[
                    task.urgency === 'overdue'
                        ? 'border-rose-200 dark:border-rose-900/70 hover:border-rose-300'
                        : task.urgency === 'danger'
                          ? 'border-rose-200 dark:border-rose-900/60 hover:border-rose-300'
                          : task.urgency === 'warning'
                            ? 'border-amber-200 dark:border-amber-900/60 hover:border-amber-300'
                            : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                ]"
            >
                <div class="space-y-3">
                    <!-- Top row: Subject & Badges -->
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2.5 py-1 rounded-md">
                                <GraduationCap class="size-3.5" />
                                {{ task.subject ? task.subject.name : 'Umum' }}
                            </span>
                            <span
                                v-if="task.session"
                                class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border"
                                :class="task.session === 'bengkel'
                                    ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60'
                                    : 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60'"
                            >
                                {{ task.session === 'bengkel' ? 'Bengkel' : 'Teori' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <Badge
                                variant="outline"
                                class="text-[10px] font-medium py-0.5 px-2"
                                :class="priorityBadge(task.priority).color"
                            >
                                {{ priorityBadge(task.priority).label }}
                            </Badge>

                            <Badge
                                variant="outline"
                                class="text-[10px] font-medium py-0.5 px-2"
                                :class="statusBadge(task.status).color"
                            >
                                {{ statusBadge(task.status).label }}
                            </Badge>
                        </div>
                    </div>

                    <!-- Title -->
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2 leading-snug"
                    >
                        {{ task.title }}
                    </h3>

                    <!-- Description preview if any -->
                    <p v-if="task.description" class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                        {{ task.description }}
                    </p>
                </div>

                <!-- Bottom row: Deadline info & Actions -->
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <!-- Deadline & Urgency -->
                    <div class="flex flex-col min-w-0">
                        <div class="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400 truncate">
                            <Clock class="size-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                            <span class="truncate">{{ task.formatted_deadline }}</span>
                        </div>

                        <!-- Urgency pill -->
                        <div v-if="getUrgencyBadge(task)" class="mt-1">
                            <span
                                class="inline-flex items-center gap-1 text-[10px] font-medium px-2 py-0.5 rounded-full border"
                                :class="getUrgencyBadge(task)?.class"
                            >
                                <span class="size-1.5 rounded-full" :class="getUrgencyBadge(task)?.dot"></span>
                                {{ getUrgencyBadge(task)?.text }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Actions: Centang Selesai (Pindah ke Histori), Edit Status, Delete -->
                    <div class="flex items-center gap-1 shrink-0" @click.stop>
                        <button
                            type="button"
                            @click="toggleComplete(task, $event)"
                            title="Tandai selesai & pindahkan ke histori"
                            class="flex size-7 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors cursor-pointer"
                        >
                            <CheckCircle2 class="size-4" />
                        </button>

                        <button
                            type="button"
                            @click="openStatusModal(task)"
                            title="Update status tugas"
                            class="flex size-7 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors cursor-pointer"
                        >
                            <Edit3 class="size-3.5" />
                        </button>

                        <button
                            type="button"
                            @click="confirmDeleteTask(task, $event)"
                            title="Hapus tugas"
                            class="flex size-7 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                        >
                            <Trash2 class="size-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 p-12 text-center space-y-4 shadow-2xs"
        >
            <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                <Calendar class="size-7" />
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Tidak ada tugas aktif</h3>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Semua tugas telah diselesaikan atau belum ada tugas yang dibuat.
                </p>
            </div>
            <div class="flex items-center justify-center gap-2 pt-1 flex-wrap">
                <Button
                    @click="openCreateModal"
                    class="h-9 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs px-4 cursor-pointer"
                >
                    <Plus class="size-4 mr-1.5" />
                    Tambah Tugas
                </Button>
                <Link
                    v-if="counts.completed > 0"
                    href="/tasks/history"
                    class="inline-flex items-center h-9 px-3.5 rounded-lg text-xs font-semibold border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 transition-colors"
                >
                    <History class="size-3.5 mr-1.5" />
                    <span>Lihat Histori Selesai ({{ counts.completed }})</span>
                </Link>
            </div>
        </div>

        <!-- Modals -->
        <TaskStatusModal
            :open="isStatusModalOpen"
            @update:open="isStatusModalOpen = $event"
            :task="selectedTaskForStatus"
            @edit-full="openEditModal"
        />

        <TaskFormModal
            :open="isFormModalOpen"
            @update:open="isFormModalOpen = $event"
            :task="taskToEdit"
            :subjects="subjects"
        />

        <TaskDetailModal
            :open="isDetailModalOpen"
            @update:open="isDetailModalOpen = $event"
            :task="selectedTaskForDetail"
            @edit="openEditModal"
        />

        <!-- Delete Task Modal -->
        <DeleteConfirmModal
            :open="isDeleteModalOpen"
            @update:open="isDeleteModalOpen = $event"
            title="Hapus Tugas"
            :item-name="taskToDelete?.title"
            description="Apakah Anda yakin ingin menghapus tugas ini secara permanen?"
            confirm-text="Ya, Hapus Tugas"
            :loading="isDeletingTask"
            @confirm="handleDeleteTask"
        />
    </div>
</template>
