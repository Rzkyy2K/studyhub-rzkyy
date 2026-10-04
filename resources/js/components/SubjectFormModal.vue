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
import type { Subject } from '@/types';

const props = defineProps<{
    open: boolean;
    subject?: Subject | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'saved'): void;
}>();

const page = usePage();
const currentActiveSession = computed<'teori' | 'bengkel'>(() => {
    return (page.props.school_session as any)?.current || 'teori';
});

const isEditing = computed(() => !!props.subject);

const form = useForm({
    name: '',
    teacher: '',
    room: '',
    session: 'teori' as 'teori' | 'bengkel',
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            if (props.subject) {
                form.name = props.subject.name;
                form.teacher = props.subject.teacher || '';
                form.room = props.subject.room || '';
                form.session = (props.subject.session as 'teori' | 'bengkel') || currentActiveSession.value;
            } else {
                form.reset();
                form.session = currentActiveSession.value;
            }
            form.clearErrors();
        }
    },
    { immediate: true },
);

const save = () => {
    if (isEditing.value && props.subject) {
        form.put(`/subjects/${props.subject.id}`, {
            onSuccess: () => {
                toast.success('Mata pelajaran berhasil diperbarui.');
                emit('update:open', false);
                emit('saved');
            },
        });
    } else {
        form.post('/subjects', {
            onSuccess: () => {
                toast.success('Mata pelajaran berhasil ditambahkan.');
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
                    {{ isEditing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' }}
                </DialogTitle>
                <DialogDescription class="text-xs text-slate-500 dark:text-slate-400">
                    {{ isEditing ? 'Perbarui informasi mata pelajaran, guru pengajar, dan ruangan.' : 'Tambahkan mata pelajaran baru untuk jadwal dan tugas sekolah.' }}
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
                    <span v-if="form.errors.session" class="text-xs text-rose-500">{{ form.errors.session }}</span>
                </div>

                <!-- Nama Mata Pelajaran -->
                <div class="space-y-1.5">
                    <Label for="sub_name" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Nama Mata Pelajaran *</Label>
                    <Input
                        id="sub_name"
                        v-model="form.name"
                        required
                        placeholder="Contoh: Pemrograman Web / Matematika"
                        class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                    />
                    <span v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</span>
                </div>

                <!-- Guru Pengajar -->
                <div class="space-y-1.5">
                    <Label for="sub_teacher" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Guru Pengajar (Opsional)</Label>
                    <Input
                        id="sub_teacher"
                        v-model="form.teacher"
                        placeholder="Contoh: Pak Budi Santoso, S.Kom"
                        class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                    />
                    <span v-if="form.errors.teacher" class="text-xs text-rose-500">{{ form.errors.teacher }}</span>
                </div>

                <!-- Ruangan Kelas -->
                <div class="space-y-1.5">
                    <Label for="sub_room" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Ruangan Kelas (Opsional)</Label>
                    <Input
                        id="sub_room"
                        v-model="form.room"
                        placeholder="Contoh: Lab Komputer 2 / Ruang 12"
                        class="h-9.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100"
                    />
                    <span v-if="form.errors.room" class="text-xs text-rose-500">{{ form.errors.room }}</span>
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
                        <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Mapel' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
