<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    BookOpen,
    Calendar,
    CalendarDays,
    CheckCircle2,
    CheckSquare,
    Clock,
    Flame,
    GraduationCap,
    ListTodo,
    MapPin,
    Plus,
    Sparkles,
    Square,
    Target,
    User,
    Wrench,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import ScheduleFormModal from '@/components/ScheduleFormModal.vue';
import TaskDetailModal from '@/components/TaskDetailModal.vue';
import TaskFormModal from '@/components/TaskFormModal.vue';
import TodoFormModal from '@/components/TodoFormModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { useSchoolSession } from '@/composables/useSchoolSession';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, DashboardStats, Schedule, SchoolSessionInfo, SchoolSessionType, Subject, Task, Todo } from '@/types';

defineOptions({
    layout: AppLayout,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const props = defineProps<{
    stats: DashboardStats;
    focus_tasks: Task[];
    today_schedules: Schedule[];
    subjects: Subject[];
    current_day: string;
    formatted_date: string;
    active_session?: SchoolSessionType;
    session_info?: SchoolSessionInfo;
    pending_todos_list?: Todo[];
}>();

const page = usePage();
const { isBengkel, isTeori } = useSchoolSession();
const userName = computed(() => page.props.auth?.user?.name || 'Pelajar');

// Dynamic greeting based on time of day
const greetingText = computed(() => {
    const hour = new Date().getHours();
    if (hour >= 4 && hour < 11) return 'Selamat pagi';
    if (hour >= 11 && hour < 15) return 'Selamat siang';
    if (hour >= 15 && hour < 18) return 'Selamat sore';
    return 'Selamat malam';
});

// Modals
const isTaskFormOpen = ref(false);
const isScheduleFormOpen = ref(false);
const isTodoFormOpen = ref(false);
const isDetailOpen = ref(false);
const selectedTask = ref<Task | null>(null);
const taskToEdit = ref<Task | null>(null);

const openDetail = (task: Task) => {
    selectedTask.value = task;
    isDetailOpen.value = true;
};

const handleEditTaskFromDetail = (task: Task) => {
    taskToEdit.value = task;
    isTaskFormOpen.value = true;
};

const toggleTodo = (todo: Todo) => {
    router.patch(
        `/todos/${todo.id}/toggle`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    todo.status === 'pending'
                        ? 'To-Do diselesaikan! 🎉'
                        : 'To-Do ditandai belum selesai.',
                );
            },
        },
    );
};

// Urgency badge styling
const getUrgencyBadge = (task: Task) => {
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
    return {
        text: task.formatted_deadline || 'Normal',
        class: 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
        dot: 'bg-slate-400',
    };
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

// Status label
const statusText = (status: string) => {
    if (status === 'completed') return 'Selesai';
    if (status === 'in_progress') return 'Sedang dikerjakan';
    return 'Belum dikerjakan';
};
</script>

<template>
    <Head title="Dashboard - StudyHub" />

    <div class="space-y-6 sm:space-y-7">
        <!-- Header Dashboard: Greeting & Quick Actions -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-1">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                    {{ greetingText }}, {{ userName }} 👋
                </h1>
                <div class="flex items-center gap-2 flex-wrap pt-0.5">
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800/80 px-2.5 py-1 rounded-full border border-slate-200/80 dark:border-slate-700/80">
                        <Calendar class="size-3.5 text-indigo-500 dark:text-indigo-400" />
                        <span>{{ formatted_date }}</span>
                    </span>
                    <span
                        v-if="session_info"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border"
                        :class="session_info.active_session === 'teori'
                            ? 'bg-indigo-50/90 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border-indigo-200/70 dark:border-indigo-900/60'
                            : 'bg-amber-50/90 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200/70 dark:border-amber-900/60'"
                    >
                        <span class="size-1.5 rounded-full" :class="session_info.active_session === 'teori' ? 'bg-indigo-500' : 'bg-amber-500'"></span>
                        {{ session_info.label }}
                    </span>
                </div>
            </div>

            <!-- Quick Action Area -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <Button
                    @click="isTaskFormOpen = true; taskToEdit = null"
                    size="sm"
                    class="h-9 gap-1.5 text-white shadow-xs font-medium text-xs rounded-lg cursor-pointer transition-all"
                    :class="isBengkel
                        ? 'bg-amber-600 hover:bg-amber-700 hover:shadow-amber-500/20'
                        : 'bg-indigo-600 hover:bg-indigo-700 hover:shadow-indigo-500/20'"
                >
                    <Plus class="size-4" />
                    <span>Tambah Tugas</span>
                </Button>

                <Button
                    @click="isScheduleFormOpen = true"
                    size="sm"
                    variant="outline"
                    class="h-9 gap-1.5 border-slate-200/80 dark:border-slate-800/80 bg-white/75 dark:bg-slate-900/75 backdrop-blur-md text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium text-xs rounded-lg shadow-2xs cursor-pointer transition-all"
                >
                    <Plus class="size-4 text-slate-400" />
                    <span>Tambah Jadwal</span>
                </Button>

                <Button
                    @click="isTodoFormOpen = true"
                    size="sm"
                    variant="outline"
                    class="h-9 gap-1.5 border-slate-200/80 dark:border-slate-800/80 bg-white/75 dark:bg-slate-900/75 backdrop-blur-md text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium text-xs rounded-lg shadow-2xs cursor-pointer transition-all"
                >
                    <Plus class="size-4 text-slate-400" />
                    <span>Tambah To-Do</span>
                </Button>
            </div>
        </div>

        <!-- SECTION: JADWAL PELAJARAN HARI INI -->
        <div class="space-y-3.5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl text-white shadow-xs shrink-0"
                        :class="isBengkel
                            ? 'bg-amber-600 dark:bg-amber-500 shadow-amber-500/20'
                            : 'bg-indigo-600 dark:bg-indigo-500 shadow-indigo-500/20'"
                    >
                        <CalendarDays class="size-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                                Jadwal Pelajaran Hari Ini
                            </h2>
                            <span
                                v-if="today_schedules && today_schedules.length > 0"
                                class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700/80"
                            >
                                {{ today_schedules.length }} Pelajaran
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Mata pelajaran untuk hari {{ formatted_date.split(',')[0] }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- List or Grid of today's classes -->
            <div v-if="today_schedules && today_schedules.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
                <div
                    v-for="item in today_schedules"
                    :key="item.id"
                    class="relative flex flex-col justify-between rounded-xl border p-4 transition-all duration-200 shadow-2xs hover:shadow-xs"
                    :class="item.is_active_now
                        ? (isBengkel
                            ? 'border-amber-400/80 dark:border-amber-500/80 bg-gradient-to-br from-amber-50/70 via-white to-amber-50/30 dark:from-amber-950/40 dark:via-slate-900 dark:to-amber-950/20 ring-2 ring-amber-500/20 shadow-sm shadow-amber-500/10'
                            : 'border-indigo-400/80 dark:border-indigo-500/80 bg-gradient-to-br from-indigo-50/70 via-white to-indigo-50/30 dark:from-indigo-950/40 dark:via-slate-900 dark:to-indigo-950/20 ring-2 ring-indigo-500/20 shadow-sm shadow-indigo-500/10')
                        : 'border-slate-200/80 dark:border-slate-800/80 bg-white/75 dark:bg-slate-900/75 backdrop-blur-md hover:border-slate-300 dark:hover:border-slate-700'"
                >
                    <div class="space-y-2.5">
                        <!-- Top status and time -->
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded-md"
                                :class="item.is_active_now
                                    ? (isBengkel
                                        ? 'bg-amber-100/80 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300'
                                        : 'bg-indigo-100/80 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300')
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                            >
                                <Clock class="size-3.5" :class="item.is_active_now ? (isBengkel ? 'text-amber-500 dark:text-amber-400' : 'text-indigo-500 dark:text-indigo-400') : 'text-slate-400 dark:text-slate-500'" />
                                {{ item.formatted_time_range }}
                            </span>
                            <span
                                v-if="item.is_active_now"
                                class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800 shrink-0"
                            >
                                <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Sedang Berlangsung
                            </span>
                        </div>

                        <!-- Subject Name -->
                        <div class="text-base font-bold text-slate-900 dark:text-white leading-snug line-clamp-2">
                            {{ item.subject ? item.subject.name : 'Pelajaran' }}
                        </div>
                    </div>

                    <!-- Meta: Room & Teacher -->
                    <div class="mt-3.5 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <span v-if="item.room || item.subject?.room" class="inline-flex items-center gap-1 truncate max-w-[50%]" :title="item.room || item.subject?.room || undefined">
                            <MapPin class="size-3 text-slate-400 dark:text-slate-500 shrink-0" />
                            <span class="truncate">{{ item.room || item.subject?.room }}</span>
                        </span>
                        <span v-else class="text-slate-400 text-[11px]">-</span>

                        <span v-if="item.subject?.teacher" class="inline-flex items-center gap-1 truncate max-w-[50%] font-medium text-slate-700 dark:text-slate-300" :title="item.subject.teacher">
                            <User class="size-3 text-slate-400 dark:text-slate-500 shrink-0" />
                            <span class="truncate">{{ item.subject.teacher }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty state when no classes today -->
            <div
                v-else
                class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-white/60 dark:bg-slate-900/60 p-6 sm:p-7 text-center transition-all shadow-2xs"
            >
                <div class="flex flex-col items-center justify-center max-w-sm mx-auto space-y-2.5">
                    <div
                        class="flex size-12 items-center justify-center rounded-2xl"
                        :class="isBengkel
                            ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-500 dark:text-amber-400'
                            : 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 dark:text-indigo-400'"
                    >
                        <CalendarDays class="size-6" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            Tidak ada jadwal pelajaran hari ini
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Hari {{ formatted_date.split(',')[0] }} bebas jam kelas. Nikmati waktu istirahat atau manfaatkan untuk belajar mandiri! 🌴
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active School Session Banner (Teori / Bengkel) -->
        <div
            v-if="session_info"
            class="relative overflow-hidden rounded-2xl border p-4 sm:p-5 transition-all shadow-2xs"
            :class="[
                session_info.active_session === 'teori'
                    ? 'border-indigo-200/80 dark:border-indigo-900/60 bg-gradient-to-r from-indigo-50/80 via-white to-indigo-50/30 dark:from-indigo-950/30 dark:via-slate-900 dark:to-indigo-950/15'
                    : 'border-amber-200/80 dark:border-amber-900/60 bg-gradient-to-r from-amber-50/80 via-white to-amber-50/30 dark:from-amber-950/30 dark:via-slate-900 dark:to-amber-950/15'
            ]"
        >
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl text-white shadow-xs"
                        :class="session_info.active_session === 'teori' ? 'bg-indigo-600 shadow-indigo-500/20' : 'bg-amber-600 shadow-amber-500/20'"
                    >
                        <BookOpen v-if="session_info.active_session === 'teori'" class="size-5.5" />
                        <Wrench v-else class="size-5.5" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full"
                                :class="session_info.active_session === 'teori'
                                    ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                                    : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'"
                            >
                                {{ session_info.label }} {{ session_info.is_viewing_official ? 'Aktif Resmi' : '(Mode Tampilan)' }}
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                • Sisa {{ session_info.days_remaining }} hari masa berlaku sesi resmi ({{ session_info.official_label }})
                            </span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 mt-1">
                            {{ session_info.subtitle }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Masa berlaku resmi: <span class="font-medium text-slate-700 dark:text-slate-300">{{ session_info.period_range }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto text-xs text-slate-500 dark:text-slate-400">
                    <span class="hidden md:inline">Ganti tampilan atau sesi kapan saja melalui tombol sesi di bilah atas ↗</span>
                </div>
            </div>

            <!-- Notice when viewing non-official session -->
            <div
                v-if="!session_info.is_viewing_official"
                class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white/80 dark:bg-slate-900/80 p-3 border border-slate-200/80 dark:border-slate-800 text-xs shadow-2xs"
            >
                <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                    <Sparkles class="size-4 text-amber-500 shrink-0" />
                    <span>
                        Anda sedang membuka data <strong>{{ session_info.label }}</strong>. Waktu berlaku sesi resmi tetap di <strong>{{ session_info.official_label }}</strong> s.d. {{ session_info.period_end }} (tidak ter-reset).
                    </span>
                </div>
                <button
                    type="button"
                    @click="router.post('/session/switch', { session: session_info.official_session }, { preserveScroll: true })"
                    class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer text-xs"
                >
                    Kembali ke {{ session_info.official_label }} →
                </button>
            </div>
        </div>

        <!-- 4 Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            <!-- Tugas Belum Selesai -->
            <Card class="border border-slate-200/80 dark:border-slate-800/80 bg-white/75 dark:bg-slate-900/75 backdrop-blur-md shadow-2xs hover:shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all group rounded-xl">
                <CardContent class="p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400">Tugas Belum Selesai</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 group-hover:scale-105 transition-transform">
                            <Clock class="size-4.5" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <div>
                            <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ stats.uncompleted_tasks }}
                            </span>
                            <span class="text-xs text-slate-400 dark:text-slate-500 ml-1.5 font-medium">tugas</span>
                        </div>
                        <span class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 hidden sm:inline">Perlu diselesaikan</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Deadline Hari Ini -->
            <Card class="border border-slate-200/80 dark:border-slate-800/80 bg-white/75 dark:bg-slate-900/75 backdrop-blur-md shadow-2xs hover:shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all group rounded-xl">
                <CardContent class="p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400">Deadline Hari Ini</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 group-hover:scale-105 transition-transform">
                            <Flame class="size-4.5" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <div>
                            <span class="text-2xl sm:text-3xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
                                {{ stats.due_today_tasks }}
                            </span>
                            <span class="text-xs text-slate-400 dark:text-slate-500 ml-1.5 font-medium">deadline</span>
                        </div>
                        <span class="text-[11px] font-medium text-amber-600 dark:text-amber-400 hidden sm:inline">Mendesak</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Tugas Selesai -->
            <Card class="border border-slate-200/80 dark:border-slate-800/80 bg-white/75 dark:bg-slate-900/75 backdrop-blur-md shadow-2xs hover:shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all group rounded-xl">
                <CardContent class="p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400">Tugas Selesai</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                            <CheckCircle2 class="size-4.5" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <div>
                            <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ stats.completed_tasks }}
                            </span>
                            <span class="text-xs text-slate-400 dark:text-slate-500 ml-1.5 font-medium">selesai</span>
                        </div>
                        <span class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 hidden sm:inline">Terselesaikan</span>
                    </div>
                </CardContent>
            </Card>

            <!-- To-Do Aktif -->
            <Card class="border border-slate-200/80 dark:border-slate-800/80 bg-white/75 dark:bg-slate-900/75 backdrop-blur-md shadow-2xs hover:shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all group rounded-xl">
                <CardContent class="p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400">To-Do Aktif</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 group-hover:scale-105 transition-transform">
                            <Target class="size-4.5" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <div>
                            <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ stats.pending_todos }}
                            </span>
                            <span class="text-xs text-slate-400 dark:text-slate-500 ml-1.5 font-medium">aktivitas</span>
                        </div>
                        <span class="text-[11px] font-medium text-sky-600 dark:text-sky-400 hidden sm:inline">Catatan harian</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Main Dashboard Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-7">
            <!-- SECTION G: FOKUS HARI INI (Tugas deadline terdekat) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 shrink-0">
                            <Target class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                                Fokus Hari Ini
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Prioritaskan tugas mendesak untuk diselesaikan lebih dulu.
                            </p>
                        </div>
                    </div>

                    <Link
                        href="/tasks"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                    >
                        <span>Semua Tugas</span>
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>

                <!-- Focus Task List -->
                <div v-if="focus_tasks && focus_tasks.length > 0" class="space-y-3">
                    <div
                        v-for="task in focus_tasks"
                        :key="task.id"
                        @click="openDetail(task)"
                        class="group relative flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border bg-white/75 dark:bg-slate-900/75 backdrop-blur-md p-4 transition-all duration-200 shadow-2xs hover:shadow-md cursor-pointer overflow-hidden"
                        :class="[
                            task.urgency === 'overdue'
                                ? 'border-rose-200/90 dark:border-rose-900/80 hover:border-rose-300 dark:hover:border-rose-800'
                                : task.urgency === 'danger'
                                  ? 'border-rose-200/80 dark:border-rose-900/60 hover:border-rose-300'
                                  : task.urgency === 'warning'
                                    ? 'border-amber-200/80 dark:border-amber-900/60 hover:border-amber-300'
                                    : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                        ]"
                    >
                        <!-- Left indicator stripe -->
                        <div
                            class="absolute left-0 top-0 bottom-0 w-1.5 transition-all group-hover:w-2"
                            :class="[
                                task.urgency === 'overdue' || task.urgency === 'danger'
                                    ? 'bg-rose-500'
                                    : task.urgency === 'warning'
                                      ? 'bg-amber-500'
                                      : 'bg-indigo-500 dark:bg-indigo-400'
                            ]"
                        />

                        <div class="pl-2 space-y-1.5 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-slate-900 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors text-sm sm:text-base leading-snug">
                                    {{ task.title }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2.5 text-xs text-slate-500 dark:text-slate-400 flex-wrap">
                                <span class="inline-flex items-center gap-1 font-medium text-slate-700 dark:text-slate-300">
                                    <GraduationCap class="size-3.5 text-indigo-500 dark:text-indigo-400" />
                                    {{ task.subject ? task.subject.name : 'Umum' }}
                                </span>

                                <span class="text-slate-300 dark:text-slate-700">•</span>

                                <span class="inline-flex items-center gap-1">
                                    <Clock class="size-3.5 text-slate-400 dark:text-slate-500" />
                                    Deadline: <strong class="font-medium text-slate-700 dark:text-slate-200">{{ task.deadline_relative || task.formatted_deadline }}</strong>
                                </span>
                            </div>
                        </div>

                        <!-- Right Badges -->
                        <div class="pl-2 sm:pl-0 flex items-center gap-2 shrink-0 self-start sm:self-center">
                            <!-- Urgency badge -->
                            <Badge
                                variant="outline"
                                class="text-[11px] font-medium py-0.5"
                                :class="getUrgencyBadge(task).class"
                            >
                                <span class="size-1.5 rounded-full mr-1.5" :class="getUrgencyBadge(task).dot"></span>
                                {{ getUrgencyBadge(task).text }}
                            </Badge>

                            <!-- Priority badge -->
                            <Badge
                                variant="outline"
                                class="text-[11px] font-medium py-0.5"
                                :class="priorityBadge(task.priority).color"
                            >
                                {{ priorityBadge(task.priority).label }}
                            </Badge>

                            <!-- Status text badge -->
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 hidden md:inline">
                                {{ statusText(task.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty state for Focus Tasks -->
                <div
                    v-else
                    class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 p-8 text-center space-y-3 shadow-2xs"
                >
                    <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                        <Sparkles class="size-6" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Semua tugas beres! 🎉</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tidak ada tugas mendesak yang belum selesai saat ini.</p>
                    </div>
                    <Button
                        @click="isTaskFormOpen = true; taskToEdit = null"
                        size="sm"
                        class="h-8.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white mt-1 cursor-pointer"
                    >
                        <Plus class="size-3.5 mr-1" />
                        Tambah Tugas Baru
                    </Button>
                </div>
            </div>

            <!-- SECTION H: AKTIVITAS TO-DO (To-Do List Cepat) -->
            <div class="lg:col-span-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 shrink-0">
                            <ListTodo class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                                Aktivitas To-Do
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Catatan harian & target belajar singkat.
                            </p>
                        </div>
                    </div>

                    <Link
                        href="/todos"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                    >
                        <span>Semua To-Do</span>
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>

                <!-- To-Do Items List -->
                <div v-if="pending_todos_list && pending_todos_list.length > 0" class="space-y-2.5">
                    <div
                        v-for="todo in pending_todos_list"
                        :key="todo.id"
                        class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/80 dark:border-slate-800/80 bg-white/75 dark:bg-slate-900/75 backdrop-blur-md p-3 hover:border-slate-300 dark:hover:border-slate-700 transition-all shadow-2xs group"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <button
                                type="button"
                                @click="toggleTodo(todo)"
                                title="Tandai selesai"
                                class="text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors cursor-pointer shrink-0"
                            >
                                <CheckSquare v-if="todo.status === 'completed'" class="size-4.5 text-emerald-600 dark:text-emerald-400" />
                                <Square v-else class="size-4.5" />
                            </button>
                            <div class="min-w-0">
                                <p
                                    class="text-xs sm:text-sm font-semibold truncate transition-colors"
                                    :class="todo.status === 'completed' ? 'text-slate-400 line-through' : 'text-slate-800 dark:text-slate-200'"
                                >
                                    {{ todo.title }}
                                </p>
                                <p v-if="todo.due_date" class="text-[11px] text-slate-400 dark:text-slate-500">
                                    Target: {{ todo.due_date }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <Badge
                                variant="outline"
                                class="text-[10px] font-medium py-0.2 capitalize"
                                :class="todo.priority === 'high' ? 'border-rose-200 text-rose-700 bg-rose-50 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300' : todo.priority === 'medium' ? 'border-amber-200 text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-300' : 'border-slate-200 text-slate-600 bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300'"
                            >
                                {{ todo.priority }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <!-- Empty state for To-Do -->
                <div
                    v-else
                    class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 p-7 text-center space-y-3 shadow-2xs"
                >
                    <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                        <CheckCircle2 class="size-6" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">To-Do beres! 🎉</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tidak ada aktivitas to-do yang tertunda saat ini.</p>
                    </div>
                    <Button
                        @click="isTodoFormOpen = true"
                        size="sm"
                        variant="outline"
                        class="h-8.5 text-xs border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 mt-1 cursor-pointer"
                    >
                        <Plus class="size-3.5 mr-1" />
                        Tambah Aktivitas To-Do
                    </Button>
                </div>
            </div>
        </div>

        <!-- Modals for Quick Actions -->
        <TaskFormModal
            :open="isTaskFormOpen"
            @update:open="isTaskFormOpen = $event"
            :task="taskToEdit"
            :subjects="subjects"
        />

        <ScheduleFormModal
            :open="isScheduleFormOpen"
            @update:open="isScheduleFormOpen = $event"
            :subjects="subjects"
        />

        <TodoFormModal
            :open="isTodoFormOpen"
            @update:open="isTodoFormOpen = $event"
        />

        <TaskDetailModal
            :open="isDetailOpen"
            @update:open="isDetailOpen = $event"
            :task="selectedTask"
            @edit="handleEditTaskFromDetail"
        />
    </div>
</template>
