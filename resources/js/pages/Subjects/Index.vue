<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BookOpen,
    Calendar,
    CheckSquare,
    DoorClosed,
    Edit3,
    GraduationCap,
    Plus,
    Search,
    Trash2,
    User,
    Wrench,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import SubjectFormModal from '@/components/SubjectFormModal.vue';
import { Badge } from '@/components/ui/badge';
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
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Subject } from '@/types';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    subjects: Subject[];
    current_session?: 'teori' | 'bengkel';
    active_tab?: 'all' | 'teori' | 'bengkel';
    counts?: {
        all: number;
        teori: number;
        bengkel: number;
    };
}>();

const switchSessionTab = (tab: string) => {
    router.get(
        '/subjects',
        { session: tab },
        { preserveState: true, preserveScroll: true },
    );
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mata Pelajaran', href: '/subjects' },
];

const searchQuery = ref('');
const isFormModalOpen = ref(false);
const subjectToEdit = ref<Subject | null>(null);

// Delete state
const isDeleteModalOpen = ref(false);
const subjectToDelete = ref<Subject | null>(null);
const isDeleting = ref(false);

const filteredSubjects = computed(() => {
    if (!searchQuery.value.trim()) return props.subjects;
    const q = searchQuery.value.toLowerCase().trim();
    return props.subjects.filter((s) => {
        const nameMatch = s.name.toLowerCase().includes(q);
        const teacherMatch = s.teacher ? s.teacher.toLowerCase().includes(q) : false;
        const roomMatch = s.room ? s.room.toLowerCase().includes(q) : false;
        return nameMatch || teacherMatch || roomMatch;
    });
});

const openAddModal = () => {
    subjectToEdit.value = null;
    isFormModalOpen.value = true;
};

const openEditModal = (subject: Subject) => {
    subjectToEdit.value = subject;
    isFormModalOpen.value = true;
};

const confirmDelete = (subject: Subject) => {
    subjectToDelete.value = subject;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (!subjectToDelete.value) return;
    isDeleting.value = true;
    router.delete(`/subjects/${subjectToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Mata pelajaran berhasil dihapus.');
            isDeleteModalOpen.value = false;
            subjectToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

// Summary metrics
const totalTasksCount = computed(() => {
    return props.subjects.reduce((acc, curr) => acc + (curr.tasks_count || 0), 0);
});

const totalSchedulesCount = computed(() => {
    return props.subjects.reduce((acc, curr) => acc + (curr.schedules_count || 0), 0);
});
</script>

<template>
    <Head title="Mata Pelajaran - StudyHub" />

    <div class="space-y-6 pb-20 md:pb-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                        Mata Pelajaran
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Kelola data mata pelajaran, nama guru pengajar, dan ruangan kelas sekolah per sesi.
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <Button
                        @click="openAddModal"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs font-medium text-xs h-9.5 px-4"
                    >
                        <Plus class="mr-1.5 size-4" />
                        Tambah Mata Pelajaran
                    </Button>
                </div>
            </div>

            <!-- Session Filter Tabs -->
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 dark:border-slate-800 pb-3">
                <button
                    type="button"
                    @click="switchSessionTab('teori')"
                    :class="[
                        'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all',
                        active_tab === 'teori'
                            ? 'bg-indigo-600 text-white shadow-xs ring-1 ring-indigo-600'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                    ]"
                >
                    <BookOpen class="size-3.5" :class="active_tab === 'teori' ? 'text-white' : 'text-indigo-500'" />
                    <span>Sesi Teori</span>
                    <span
                        v-if="current_session === 'teori'"
                        class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                        :class="active_tab === 'teori' ? 'bg-indigo-700 text-indigo-100' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'"
                    >
                        Aktif
                    </span>
                    <span class="text-[11px] opacity-80 font-normal">({{ counts?.teori ?? 0 }})</span>
                </button>

                <button
                    type="button"
                    @click="switchSessionTab('bengkel')"
                    :class="[
                        'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all',
                        active_tab === 'bengkel'
                            ? 'bg-amber-600 text-white shadow-xs ring-1 ring-amber-600'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                    ]"
                >
                    <Wrench class="size-3.5" :class="active_tab === 'bengkel' ? 'text-white' : 'text-amber-500'" />
                    <span>Sesi Bengkel</span>
                    <span
                        v-if="current_session === 'bengkel'"
                        class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                        :class="active_tab === 'bengkel' ? 'bg-amber-700 text-amber-100' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'"
                    >
                        Aktif
                    </span>
                    <span class="text-[11px] opacity-80 font-normal">({{ counts?.bengkel ?? 0 }})</span>
                </button>

                <button
                    type="button"
                    @click="switchSessionTab('all')"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all',
                        active_tab === 'all'
                            ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 shadow-xs'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                    ]"
                >
                    <span>Semua Sesi</span>
                    <span class="text-[11px] opacity-80 font-normal">({{ counts?.all ?? 0 }})</span>
                </button>
            </div>

            <!-- Stats Bar & Search -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search Box -->
                <div class="md:col-span-2 relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-slate-400 dark:text-slate-500" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Cari nama mata pelajaran, guru, atau ruangan..."
                        class="pl-9.5 h-10 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-sm rounded-xl"
                    />
                </div>

                <!-- Stats summary badge 1 -->
                <div class="flex items-center gap-3 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-xs">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                        <BookOpen class="size-4.5" />
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Total Mapel</div>
                        <div class="text-base font-bold text-slate-900 dark:text-slate-100">{{ subjects.length }}</div>
                    </div>
                </div>

                <!-- Stats summary badge 2 -->
                <div class="flex items-center gap-3 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-xs">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                        <CheckSquare class="size-4.5" />
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Tugas Terhubung</div>
                        <div class="text-base font-bold text-slate-900 dark:text-slate-100">{{ totalTasksCount }}</div>
                    </div>
                </div>
            </div>

            <!-- Subject Grid Cards -->
            <div v-if="filteredSubjects.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="sub in filteredSubjects"
                    :key="sub.id"
                    class="group relative flex flex-col justify-between rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs hover:border-indigo-300 dark:hover:border-indigo-800 transition-all"
                >
                    <div>
                        <!-- Header with Icon & Action buttons -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex size-10 shrink-0 items-center justify-center rounded-xl border"
                                    :class="sub.session === 'bengkel'
                                        ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border-amber-100/50 dark:border-amber-900/50'
                                        : 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border-indigo-100/50 dark:border-indigo-900/50'"
                                >
                                    <component :is="sub.session === 'bengkel' ? Wrench : GraduationCap" class="size-5" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-base text-slate-900 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                            {{ sub.name }}
                                        </h3>
                                        <span
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wider uppercase border"
                                            :class="sub.session === 'bengkel'
                                                ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60'
                                                : 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60'"
                                        >
                                            {{ sub.session === 'bengkel' ? 'Bengkel' : 'Teori' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                        <User class="size-3.5 text-slate-400 dark:text-slate-500" />
                                        <span>{{ sub.teacher || 'Guru belum diatur' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    @click="openEditModal(sub)"
                                    title="Edit Mata Pelajaran"
                                    class="size-8 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white"
                                >
                                    <Edit3 class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    @click="confirmDelete(sub)"
                                    title="Hapus Mata Pelajaran"
                                    class="size-8 text-slate-400 dark:text-slate-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 dark:hover:text-rose-400"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Ruangan info -->
                        <div class="mt-4 flex items-center gap-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 px-3 py-2 text-xs text-slate-600 dark:text-slate-300 border border-slate-100 dark:border-slate-800/80">
                            <DoorClosed class="size-4 text-slate-400 dark:text-slate-500 shrink-0" />
                            <span class="font-medium">Ruangan:</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">
                                {{ sub.room || 'Belum ditentukan' }}
                            </span>
                        </div>
                    </div>

                    <!-- Footer: Linked Tasks & Schedules badges -->
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                            <CheckSquare class="size-3.5 text-indigo-500" />
                            <span>{{ sub.tasks_count || 0 }} tugas</span>
                        </div>

                        <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                            <Calendar class="size-3.5 text-amber-500" />
                            <span>{{ sub.schedules_count || 0 }} sesi jadwal</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 py-16 px-4 text-center"
            >
                <div class="flex size-14 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 mb-4 shadow-xs">
                    <BookOpen class="size-7" />
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                    {{ searchQuery ? 'Mata pelajaran tidak ditemukan' : 'Belum Ada Mata Pelajaran' }}
                </h3>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 max-w-sm">
                    {{ searchQuery ? `Tidak ditemukan mata pelajaran dengan kata kunci "${searchQuery}". Coba kata kunci lain.` : 'Tambahkan mata pelajaran untuk memudahkan pencatatan tugas dan jadwal pelajaran mingguan.' }}
                </p>
                <Button
                    v-if="!searchQuery"
                    @click="openAddModal"
                    class="mt-5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs h-9 px-4"
                >
                    <Plus class="mr-1.5 size-4" />
                    Tambah Mata Pelajaran Pertama
                </Button>
            </div>
        </div>

        <!-- Add/Edit Subject Modal -->
        <SubjectFormModal
            v-model:open="isFormModalOpen"
            :subject="subjectToEdit"
        />

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="isDeleteModalOpen" @update:open="isDeleteModalOpen = $event">
            <DialogContent class="sm:max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl">
                <DialogHeader class="space-y-2">
                    <div class="flex size-10 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 mx-auto sm:mx-0">
                        <Trash2 class="size-5" />
                    </div>
                    <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">
                        Hapus Mata Pelajaran
                    </DialogTitle>
                    <DialogDescription class="text-xs text-slate-500 dark:text-slate-400">
                        Apakah kamu yakin ingin menghapus mata pelajaran <span class="font-semibold text-slate-900 dark:text-white">"{{ subjectToDelete?.name }}"</span>?
                        Tugas atau jadwal yang terhubung akan tetap ada namun mata pelajarannya akan menjadi tidak terhubung.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="pt-3 sm:justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isDeleteModalOpen = false"
                        :disabled="isDeleting"
                        class="h-9.5 text-xs text-slate-600 dark:text-slate-400 dark:border-slate-700 dark:hover:bg-slate-800"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        @click="handleDelete"
                        :disabled="isDeleting"
                        class="h-9.5 text-xs bg-rose-600 hover:bg-rose-700 text-white font-medium"
                    >
                        <span>{{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Mapel' }}</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
</template>
