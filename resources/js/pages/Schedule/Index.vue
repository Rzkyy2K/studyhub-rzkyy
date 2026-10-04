<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarDays,
    Clock,
    Edit3,
    GraduationCap,
    MapPin,
    Plus,
    Trash2,
    User,
    Wrench,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import ScheduleFormModal from '@/components/ScheduleFormModal.vue';
import { Button } from '@/components/ui/button';
import { useSchoolSession } from '@/composables/useSchoolSession';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, DayOfWeek, Schedule, Subject } from '@/types';

defineOptions({
    layout: AppLayout,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Jadwal',
        href: '/schedule',
    },
];

const props = defineProps<{
    schedules: Schedule[];
    subjects: Subject[];
    current_day: string;
    active_session?: 'teori' | 'bengkel';
    session_filter?: string;
}>();

const selectedSession = ref(props.session_filter || props.active_session || 'teori');
const { isBengkel: isBengkelGlobal } = useSchoolSession();
const isBengkelTheme = computed(() => selectedSession.value === 'bengkel' || (selectedSession.value === 'all' && (props.active_session === 'bengkel' || isBengkelGlobal.value)));

const switchSession = (sessionVal: string) => {
    selectedSession.value = sessionVal;
    router.get(
        '/schedule',
        { session: sessionVal },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const days: { key: DayOfWeek; label: string }[] = [
    { key: 'monday', label: 'Senin' },
    { key: 'tuesday', label: 'Selasa' },
    { key: 'wednesday', label: 'Rabu' },
    { key: 'thursday', label: 'Kamis' },
    { key: 'friday', label: 'Jumat' },
];

// Active day for mobile tab view (defaults to today, or Monday if Sunday)
const activeMobileDay = ref<DayOfWeek>(
    days.some((d) => d.key === props.current_day)
        ? (props.current_day as DayOfWeek)
        : 'monday',
);

// Modals
const isFormModalOpen = ref(false);
const scheduleToEdit = ref<Schedule | null>(null);
const defaultDayForNew = ref<DayOfWeek>('monday');

// Delete confirmation dialog
const isDeleteConfirmOpen = ref(false);
const scheduleToDelete = ref<Schedule | null>(null);

// Check if URL has ?create=true
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('create') === 'true') {
    isFormModalOpen.value = true;
}

// Group schedules by day
const groupedSchedules = computed(() => {
    const map: Record<DayOfWeek, Schedule[]> = {
        monday: [],
        tuesday: [],
        wednesday: [],
        thursday: [],
        friday: [],
    };

    props.schedules.forEach((item) => {
        if (map[item.day]) {
            map[item.day].push(item);
        }
    });

    return map;
});

const openCreateModal = (day?: DayOfWeek) => {
    scheduleToEdit.value = null;
    defaultDayForNew.value = day || activeMobileDay.value;
    isFormModalOpen.value = true;
};

const openEditModal = (schedule: Schedule) => {
    scheduleToEdit.value = schedule;
    isFormModalOpen.value = true;
};

const confirmDelete = (schedule: Schedule) => {
    scheduleToDelete.value = schedule;
    isDeleteConfirmOpen.value = true;
};

const executeDelete = () => {
    if (!scheduleToDelete.value) return;
    router.delete(`/schedule/${scheduleToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Jadwal berhasil dihapus.');
            isDeleteConfirmOpen.value = false;
            scheduleToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Jadwal Pelajaran - StudyHub" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Jadwal Pelajaran
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Jadwal mingguan pelajaran sekolah dan ruangan kelas.
                </p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <Button
                    variant="outline"
                    as-child
                    class="h-9.5 gap-2 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium text-xs sm:text-sm rounded-lg"
                >
                    <Link href="/subjects">
                        <BookOpen class="size-4 text-indigo-600 dark:text-indigo-400" />
                        <span>Kelola Mapel</span>
                    </Link>
                </Button>

                <Button
                    @click="openCreateModal()"
                    class="h-9.5 gap-2 text-white shadow-xs font-medium text-xs sm:text-sm rounded-lg cursor-pointer transition-all"
                    :class="isBengkelTheme
                        ? 'bg-amber-600 hover:bg-amber-700 hover:shadow-amber-500/20'
                        : 'bg-indigo-600 hover:bg-indigo-700 hover:shadow-indigo-500/20'"
                >
                    <Plus class="size-4" />
                    <span>Tambah Jadwal</span>
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

        <!-- Mobile Day Tabs (visible only on mobile) -->
        <div class="md:hidden border-b border-slate-200 dark:border-slate-800">
            <div class="flex space-x-2 overflow-x-auto pb-px">
                <button
                    v-for="d in days"
                    :key="d.key"
                    type="button"
                    @click="activeMobileDay = d.key"
                    class="border-b-2 py-2.5 px-3 text-xs font-medium transition-colors whitespace-nowrap flex items-center gap-1.5 cursor-pointer"
                    :class="activeMobileDay === d.key
                        ? (isBengkelTheme
                            ? 'border-amber-600 dark:border-amber-400 text-amber-600 dark:text-amber-400 font-semibold'
                            : 'border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 font-semibold')
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
                >
                    <span>{{ d.label }}</span>
                    <span
                        v-if="groupedSchedules[d.key].length > 0"
                        class="size-1.5 rounded-full"
                        :class="activeMobileDay === d.key
                            ? (isBengkelTheme ? 'bg-amber-600 dark:bg-amber-400' : 'bg-indigo-600 dark:bg-indigo-400')
                            : 'bg-slate-300 dark:bg-slate-700'"
                    ></span>
                </button>
            </div>
        </div>

        <!-- Mobile View (One Day selected) -->
        <div class="md:hidden space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">
                    Hari {{ days.find(d => d.key === activeMobileDay)?.label }}
                </h2>
                <Button
                    @click="openCreateModal(activeMobileDay)"
                    size="sm"
                    variant="outline"
                    class="h-8 text-xs bg-white dark:bg-slate-900"
                    :class="isBengkelTheme
                        ? 'border-amber-200 dark:border-amber-800 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40'
                        : 'border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40'"
                >
                    <Plus class="size-3.5 mr-1" />
                    Tambah
                </Button>
            </div>

            <div v-if="groupedSchedules[activeMobileDay].length > 0" class="space-y-3">
                <div
                    v-for="item in groupedSchedules[activeMobileDay]"
                    :key="item.id"
                    class="rounded-xl border bg-white/75 dark:bg-slate-900/75 backdrop-blur-md p-4 shadow-xs space-y-2.5 transition-all"
                    :class="item.is_active_now ? 'border-indigo-400 dark:border-indigo-500 bg-indigo-50/20 dark:bg-indigo-950/30 ring-1 ring-indigo-200 dark:ring-indigo-800' : 'border-slate-200/80 dark:border-slate-800'"
                >
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold" :class="item.is_active_now ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400'">
                            <Clock class="size-3.5" />
                            {{ item.formatted_time_range }}
                        </span>

                        <span
                            v-if="item.is_active_now"
                            class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800"
                        >
                            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Sedang Berlangsung
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-base font-bold text-slate-900 dark:text-white">
                            {{ item.subject ? item.subject.name : 'Pelajaran' }}
                        </span>
                        <span
                            v-if="item.session"
                            class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border"
                            :class="item.session === 'bengkel'
                                ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60'
                                : 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60'"
                        >
                            {{ item.session === 'bengkel' ? 'Bengkel' : 'Teori' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                        <span v-if="item.subject?.teacher" class="flex items-center gap-1">
                            <User class="size-3.5 text-slate-400 dark:text-slate-500" />
                            {{ item.subject.teacher }}
                        </span>
                        <span v-if="item.room || item.subject?.room" class="flex items-center gap-1">
                            <MapPin class="size-3.5 text-slate-400 dark:text-slate-500" />
                            {{ item.room || item.subject?.room }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="openEditModal(item)"
                            class="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 px-2 py-1 rounded-md hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer"
                        >
                            <Edit3 class="size-3.5" />
                            Edit
                        </button>
                        <button
                            type="button"
                            @click="confirmDelete(item)"
                            class="flex items-center gap-1 text-xs text-rose-500 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 px-2 py-1 rounded-md hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer"
                        >
                            <Trash2 class="size-3.5" />
                            Hapus
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty for mobile day -->
            <div
                v-else
                class="rounded-xl border border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center space-y-3"
            >
                <div class="mx-auto flex size-10 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500">
                    <CalendarDays class="size-5" />
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Belum ada jadwal untuk hari ini.
                </div>
                <Button
                    @click="openCreateModal(activeMobileDay)"
                    size="sm"
                    variant="outline"
                    class="h-8 text-xs border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer"
                >
                    <Plus class="size-3.5 mr-1" />
                    Tambah Jadwal
                </Button>
            </div>
        </div>

        <!-- Desktop Weekly Grid (Senin - Jumat Columns) -->
        <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3.5 items-start">
            <div
                v-for="d in days"
                :key="d.key"
                class="rounded-xl border border-slate-200/80 dark:border-slate-800/80 bg-white/75 dark:bg-slate-900/75 backdrop-blur-md p-3 space-y-3 transition-colors"
                :class="{ 'ring-2 ring-indigo-500 border-indigo-400 dark:border-indigo-500 shadow-sm': current_day === d.key }"
            >
                <!-- Day Header -->
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            {{ d.label }}
                        </span>
                        <span
                            v-if="current_day === d.key"
                            class="rounded-full bg-indigo-100 dark:bg-indigo-950/70 px-1.5 py-0.2 text-[9px] font-semibold text-indigo-700 dark:text-indigo-300"
                        >
                            Hari Ini
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="openCreateModal(d.key)"
                        title="Tambah jadwal"
                        class="rounded-md p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer"
                    >
                        <Plus class="size-3.5" />
                    </button>
                </div>

                <!-- Schedule cards for this day -->
                <div v-if="groupedSchedules[d.key].length > 0" class="space-y-2.5">
                    <div
                        v-for="item in groupedSchedules[d.key]"
                        :key="item.id"
                        class="group relative rounded-lg border bg-slate-50/60 dark:bg-slate-800/60 p-2.5 transition-all hover:bg-white dark:hover:bg-slate-800 hover:shadow-xs"
                        :class="item.is_active_now ? 'border-indigo-400 dark:border-indigo-500 bg-indigo-50/30 dark:bg-indigo-950/40' : 'border-slate-200/80 dark:border-slate-700'"
                    >
                        <!-- Time -->
                        <div class="flex items-center justify-between text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 mb-1">
                            <span>{{ item.formatted_time_range }}</span>
                            <span
                                v-if="item.is_active_now"
                                class="size-2 rounded-full bg-emerald-500 animate-ping"
                                title="Sedang berlangsung"
                            ></span>
                        </div>

                        <!-- Subject -->
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100 leading-snug truncate">
                                {{ item.subject ? item.subject.name : 'Pelajaran' }}
                            </span>
                            <span
                                v-if="item.session"
                                class="shrink-0 px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider border"
                                :class="item.session === 'bengkel'
                                    ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60'
                                    : 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60'"
                            >
                                {{ item.session === 'bengkel' ? 'Bengkel' : 'Teori' }}
                            </span>
                        </div>

                        <!-- Room / Teacher -->
                        <div class="mt-1 space-y-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                            <div v-if="item.subject?.teacher" class="flex items-center gap-1 truncate">
                                <User class="size-3 shrink-0 text-slate-400 dark:text-slate-500" />
                                <span class="truncate">{{ item.subject.teacher }}</span>
                            </div>
                            <div v-if="item.room || item.subject?.room" class="flex items-center gap-1 truncate">
                                <MapPin class="size-3 shrink-0 text-slate-400 dark:text-slate-500" />
                                <span class="truncate">{{ item.room || item.subject?.room }}</span>
                            </div>
                        </div>

                        <!-- Hover Actions -->
                        <div class="mt-2 pt-1.5 border-t border-slate-200/60 dark:border-slate-700 flex items-center justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button
                                type="button"
                                @click="openEditModal(item)"
                                title="Edit"
                                class="text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 p-0.5 cursor-pointer"
                            >
                                <Edit3 class="size-3" />
                            </button>
                            <button
                                type="button"
                                @click="confirmDelete(item)"
                                title="Hapus"
                                class="text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 p-0.5 cursor-pointer"
                            >
                                <Trash2 class="size-3" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty Day in Desktop Grid -->
                <div v-else class="py-6 text-center text-slate-400 dark:text-slate-600">
                    <p class="text-[11px]">Libur / Kosong</p>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="isDeleteConfirmOpen" @update:open="isDeleteConfirmOpen = $event">
            <DialogContent class="sm:max-w-sm bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 p-6 rounded-2xl">
                <DialogHeader class="space-y-1">
                    <DialogTitle class="text-base font-bold text-slate-900 dark:text-white">
                        Hapus jadwal ini?
                    </DialogTitle>
                    <DialogDescription class="text-xs text-slate-500 dark:text-slate-400">
                        Jadwal yang dihapus tidak dapat dikembalikan.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="pt-4 flex sm:justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isDeleteConfirmOpen = false"
                        class="h-9 text-xs border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        @click="executeDelete"
                        class="h-9 text-xs bg-rose-600 hover:bg-rose-700 text-white font-medium"
                    >
                        Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Schedule Form Modal -->
        <ScheduleFormModal
            :open="isFormModalOpen"
            @update:open="isFormModalOpen = $event"
            :schedule="scheduleToEdit"
            :subjects="subjects"
            :default-day="defaultDayForNew"
        />
    </div>
</template>
