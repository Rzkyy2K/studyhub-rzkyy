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
import type { DayOfWeek, Schedule, Subject } from '@/types';

const props = defineProps<{
    open: boolean;
    schedule?: Schedule | null;
    subjects: Subject[];
    defaultDay?: DayOfWeek;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'saved'): void;
}>();

const page = usePage();
const currentActiveSession = computed<'teori' | 'bengkel'>(() => {
    return (page.props.school_session as any)?.current || 'teori';
});

const isEditing = computed(() => !!props.schedule);

const days = [
    { value: 'monday', label: 'Senin' },
    { value: 'tuesday', label: 'Selasa' },
    { value: 'wednesday', label: 'Rabu' },
    { value: 'thursday', label: 'Kamis' },
    { value: 'friday', label: 'Jumat' },
];

const form = useForm({
    day: 'monday' as DayOfWeek,
    subject_id: '' as string | number,
    new_subject_name: '',
    teacher: '',
    room: '',
    start_time: '08:00',
    end_time: '09:30',
    session: 'teori' as 'teori' | 'bengkel',
});

// Auto-fill teacher & room when subject changes
watch(
    () => form.subject_id,
    (newSubId) => {
        if (newSubId && newSubId !== 'new') {
            const selected = props.subjects.find((s) => s.id === Number(newSubId));
            if (selected) {
                if (!form.teacher && selected.teacher) {
                    form.teacher = selected.teacher;
                }
                if (!form.room && selected.room) {
                    form.room = selected.room;
                }
                if ((selected as any).session) {
                    form.session = (selected as any).session;
                }
            }
        }
    }
);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            if (props.schedule) {
                form.day = props.schedule.day;
                form.subject_id = props.schedule.subject_id || '';
                form.new_subject_name = '';
                form.teacher = props.schedule.subject?.teacher || '';
                form.room = props.schedule.room || props.schedule.subject?.room || '';
                form.start_time = props.schedule.start_time.slice(0, 5);
                form.end_time = props.schedule.end_time.slice(0, 5);
                form.session = (props.schedule.session as 'teori' | 'bengkel') || currentActiveSession.value;
            } else {
                form.reset();
                form.day = props.defaultDay || 'monday';
                form.start_time = '08:00';
                form.end_time = '09:30';
                form.session = currentActiveSession.value;
                form.subject_id = props.subjects[0]?.id || '';
            }
            form.clearErrors();
        }
    },
    { immediate: true },
);

const save = () => {
    // Client validation: end_time must be after start_time
    if (form.end_time <= form.start_time) {
        form.setError('end_time', 'Jam selesai tidak boleh lebih awal dari jam mulai.');
        return;
    }

    if (isEditing.value && props.schedule) {
        form.put(`/schedule/${props.schedule.id}`, {
            onSuccess: () => {
                toast.success('Jadwal berhasil diperbarui.');
                emit('update:open', false);
                emit('saved');
            },
        });
    } else {
        form.post('/schedule', {
            onSuccess: () => {
                toast.success('Jadwal berhasil ditambahkan.');
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
                    {{ isEditing ? 'Edit Jadwal Pelajaran' : 'Tambah Jadwal Pelajaran' }}
                </DialogTitle>
                <DialogDescription class="text-xs text-slate-500 dark:text-slate-400">
                    {{ isEditing ? 'Ubah waktu atau ruangan mata pelajaran.' : 'Atur jadwal pelajaran mingguan sekolah.' }}
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

                <!-- Hari -->
                <div class="space-y-1.5">
                    <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Hari *</Label>
                    <select
                        v-model="form.day"
                        required
                        class="w-full h-9.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600"
                    >
                        <option v-for="d in days" :key="d.value" :value="d.value">
                            {{ d.label }}
                        </option>
                    </select>
                    <span v-if="form.errors.day" class="text-xs text-rose-500">{{ form.errors.day }}</span>
                </div>

                <!-- Mata Pelajaran -->
                <div class="space-y-1.5">
                    <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Mata Pelajaran *</Label>
                    <select
                        v-model="form.subject_id"
                        class="w-full h-9.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600"
                    >
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        <option v-for="sub in subjects" :key="sub.id" :value="sub.id">
                            {{ sub.name }}
                        </option>
                        <option value="new">+ Tambah Mapel Baru...</option>
                    </select>

                    <div v-if="form.subject_id === 'new'" class="pt-1.5">
                        <Input
                            v-model="form.new_subject_name"
                            required
                            placeholder="Ketik nama mata pelajaran baru..."
                            class="h-9 text-xs border-indigo-300 dark:border-indigo-700 bg-indigo-50/30 dark:bg-indigo-950/30 dark:text-slate-100"
                        />
                    </div>
                </div>

                <!-- Guru Pengajar -->
                <div class="space-y-1.5">
                    <Label for="sch_teacher" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Guru Pengajar (Opsional)</Label>
                    <Input
                        id="sch_teacher"
                        v-model="form.teacher"
                        placeholder="Contoh: Pak Budi / Bu Ratna"
                        class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                    />
                </div>

                <!-- Ruangan -->
                <div class="space-y-1.5">
                    <Label for="sch_room" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Ruangan (Opsional)</Label>
                    <Input
                        id="sch_room"
                        v-model="form.room"
                        placeholder="Contoh: Lab RPL / Ruang 3"
                        class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                    />
                </div>

                <!-- Jam Mulai & Jam Selesai -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <Label for="sch_start" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Jam Mulai *</Label>
                        <Input
                            id="sch_start"
                            type="time"
                            v-model="form.start_time"
                            required
                            class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                        />
                        <span v-if="form.errors.start_time" class="text-xs text-rose-500">{{ form.errors.start_time }}</span>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="sch_end" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Jam Selesai *</Label>
                        <Input
                            id="sch_end"
                            type="time"
                            v-model="form.end_time"
                            required
                            class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                        />
                        <span v-if="form.errors.end_time" class="text-xs text-rose-500">{{ form.errors.end_time }}</span>
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
                        <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Jadwal' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
