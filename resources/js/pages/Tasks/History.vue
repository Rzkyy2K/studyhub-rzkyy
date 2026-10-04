<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    BookOpen,
    Calendar,
    CheckCircle2,
    Clock,
    GraduationCap,
    History,
    RotateCcw,
    Search,
    Sparkles,
    Trash2,
    Wrench,
} from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import TaskDetailModal from '@/components/TaskDetailModal.vue';
import DeleteConfirmModal from '@/components/DeleteConfirmModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    {
        title: 'Histori Selesai',
        href: '/tasks/history',
    },
];

const props = defineProps<{
    tasks: Task[];
    subjects: Subject[];
    active_session?: 'teori' | 'bengkel';
    filters: {
        search: string;
        subject_id: string;
        sort: string;
        session?: string;
    };
    total_completed: number;
}>();

// Search & Filter State
const searchInput = ref(props.filters.search || '');
const selectedSubject = ref(props.filters.subject_id || 'all');
const selectedSort = ref(props.filters.sort || 'completed_desc');
const selectedSession = ref(props.filters.session || props.active_session || 'teori');

// Modals
const isDetailModalOpen = ref(false);
const selectedTaskForDetail = ref<Task | null>(null);

const applyFilters = () => {
    router.get(
        '/tasks/history',
        {
            session: selectedSession.value,
            search: searchInput.value || undefined,
            subject_id: selectedSubject.value !== 'all' ? selectedSubject.value : undefined,
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

const openDetail = (task: Task) => {
    selectedTaskForDetail.value = task;
    isDetailModalOpen.value = true;
};

const restoreTask = (task: Task, event: Event) => {
    event.stopPropagation();
    router.patch(
        `/tasks/${task.id}/toggle`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Tugas berhasil dikembalikan ke daftar tugas aktif!');
            },
        },
    );
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
            toast.success('Tugas berhasil dihapus dari histori.');
            isDeleteModalOpen.value = false;
            taskToDelete.value = null;
        },
        onFinish: () => {
            isDeletingTask.value = false;
        },
    });
};

const isResetModalOpen = ref(false);
const isResettingHistory = ref(false);

const openResetModal = () => {
    isResetModalOpen.value = true;
};

const handleResetHistory = () => {
    isResettingHistory.value = true;
    router.post(
        '/tasks/history/reset',
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Semua histori tugas selesai berhasil di-reset.');
                isResetModalOpen.value = false;
            },
            onFinish: () => {
                isResettingHistory.value = false;
            },
        },
    );
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
</script>

<template>
    <Head title="Histori Tugas Selesai - StudyHub" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                        <span>Histori Tugas Selesai</span>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            {{ total_completed }} Selesai
                        </span>
                    </h1>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Riwayat tugas yang telah tuntas. Disimpan selama 7 hari sebelum otomatis di-reset.
                </p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <Button
                    v-if="tasks && tasks.length > 0"
                    @click="openResetModal"
                    size="sm"
                    variant="outline"
                    class="h-9 gap-1.5 text-xs text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-900/60 hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer"
                >
                    <Trash2 class="size-3.5" />
                    <span>Reset Histori Sekarang</span>
                </Button>

                <Link
                    href="/tasks"
                    class="inline-flex items-center h-9 px-3.5 rounded-lg text-xs font-medium bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors"
                >
                    <ArrowLeft class="size-3.5 mr-1.5" />
                    <span>Kembali ke Tugas Aktif</span>
                </Link>
            </div>
        </div>

        <!-- Info Banner: Reset Setiap Seminggu -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-2xl border border-emerald-200/80 dark:border-emerald-900/60 bg-gradient-to-r from-emerald-50/80 via-white to-emerald-50/30 dark:from-emerald-950/30 dark:via-slate-900 dark:to-emerald-950/15 p-4 sm:p-4.5 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs shadow-emerald-500/20">
                    <History class="size-5" />
                </div>
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                        Penyimpanan Histori 7 Hari (Reset Otomatis)
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Tugas yang telah selesai disimpan di halaman ini selama <strong>7 hari</strong> sejak diselesaikan, kemudian akan dibersihkan otomatis setiap minggunya agar database tetap optimal.
                    </p>
                </div>
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

        <!-- Filter Controls Bar: Search, Subject, Sort -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <!-- Search bar -->
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-slate-400 dark:text-slate-500" />
                <Input
                    type="text"
                    v-model="searchInput"
                    @keyup.enter="applyFilters"
                    @blur="applyFilters"
                    placeholder="Cari histori berdasarkan judul tugas..."
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

                <!-- Sort order -->
                <select
                    v-model="selectedSort"
                    @change="applyFilters"
                    class="h-9 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 px-2.5 text-xs text-slate-700 dark:text-slate-200 focus:border-indigo-600 focus:outline-none"
                >
                    <option value="completed_desc">Diselesaikan Terbaru</option>
                    <option value="completed_asc">Diselesaikan Terlama</option>
                    <option value="deadline_asc">Deadline Terdekat</option>
                </select>
            </div>
        </div>

        <!-- Task Cards Grid -->
        <div v-if="tasks && tasks.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="task in tasks"
                :key="task.id"
                @click="openDetail(task)"
                class="group relative flex flex-col justify-between rounded-xl border border-slate-200/80 dark:border-slate-800/80 bg-white/75 dark:bg-slate-900/75 backdrop-blur-md p-5 transition-all hover:shadow-md cursor-pointer hover:border-emerald-300 dark:hover:border-emerald-800 shadow-2xs"
            >
                <div class="space-y-3">
                    <!-- Top row: Subject & Session & Priority Badges -->
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-1 rounded-md">
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
                                class="text-[10px] font-semibold py-0.5 px-2 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800"
                            >
                                Selesai
                            </Badge>
                        </div>
                    </div>

                    <!-- Title -->
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 line-through line-clamp-2 leading-snug group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                        {{ task.title }}
                    </h3>

                    <!-- Description preview if any -->
                    <p v-if="task.description" class="text-xs text-slate-400 dark:text-slate-500 line-clamp-2 leading-relaxed">
                        {{ task.description }}
                    </p>

                    <!-- Meta Tags: Completed Date & Reset Countdown -->
                    <div class="pt-1 flex flex-col gap-1.5 text-xs">
                        <div class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-medium">
                            <CheckCircle2 class="size-3.5 shrink-0" />
                            <span>Selesai: {{ task.formatted_completed_at || 'Baru saja' }}</span>
                        </div>

                        <div v-if="task.days_until_reset" class="inline-flex items-center gap-1.5 text-slate-500 dark:text-slate-400 text-[11px]">
                            <Clock class="size-3 text-amber-500 shrink-0" />
                            <span>Direset dalam: <strong class="font-semibold text-slate-700 dark:text-slate-300">{{ task.days_until_reset }}</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Bottom row: Actions -->
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2" @click.stop>
                    <span class="text-[11px] text-slate-400 truncate">
                        Deadline: {{ task.formatted_deadline }}
                    </span>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <!-- Restore Button -->
                        <Button
                            @click="restoreTask(task, $event)"
                            size="sm"
                            variant="outline"
                            class="h-7.5 px-2.5 text-xs text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-900/60 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 cursor-pointer gap-1"
                            title="Kembalikan ke tugas aktif"
                        >
                            <RotateCcw class="size-3" />
                            <span>Kembalikan</span>
                        </Button>

                        <!-- Permanent Delete Button -->
                        <button
                            type="button"
                            @click="confirmDeleteTask(task, $event)"
                            title="Hapus permanen"
                            class="flex size-7.5 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
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
            <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                <CheckCircle2 class="size-7" />
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Belum ada histori tugas selesai</h3>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Ketika Anda mencentang tugas yang sudah selesai di halaman tugas, tugas akan otomatis masuk ke sini dan disimpan selama 7 hari sebelum di-reset.
                </p>
            </div>
            <Link
                href="/tasks"
                class="inline-flex items-center h-9 px-4 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
            >
                <ArrowLeft class="size-3.5 mr-1.5" />
                <span>Lihat Tugas Aktif</span>
            </Link>
        </div>

        <!-- Task Detail Modal -->
        <TaskDetailModal
            :open="isDetailModalOpen"
            @update:open="isDetailModalOpen = $event"
            :task="selectedTaskForDetail"
        />

        <!-- Delete Single Task Modal -->
        <DeleteConfirmModal
            :open="isDeleteModalOpen"
            @update:open="isDeleteModalOpen = $event"
            title="Hapus Tugas dari Histori"
            :item-name="taskToDelete?.title"
            description="Apakah Anda yakin ingin menghapus tugas ini secara permanen dari histori?"
            confirm-text="Ya, Hapus Permanen"
            :loading="isDeletingTask"
            @confirm="handleDeleteTask"
        />

        <!-- Reset All History Modal -->
        <DeleteConfirmModal
            :open="isResetModalOpen"
            @update:open="isResetModalOpen = $event"
            title="Reset Semua Histori Tugas"
            description="Apakah Anda yakin ingin menghapus semua histori tugas yang telah selesai? Tindakan ini akan menghapus seluruh rekaman tugas selesai secara permanen dan tidak dapat dibatalkan."
            confirm-text="Ya, Reset Semua Histori"
            :loading="isResettingHistory"
            @confirm="handleResetHistory"
        />
    </div>
</template>
