<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    BookOpen,
    Calendar,
    Check,
    ChevronDown,
    Clock,
    Eye,
    PlusCircle,
    RotateCw,
    Sparkles,
    Wrench,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import DeleteConfirmModal from '@/components/DeleteConfirmModal.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { SchoolSessionInfo } from '@/types';

const page = usePage();
const sessionInfo = computed<SchoolSessionInfo | undefined>(
    () => page.props.school_session as SchoolSessionInfo | undefined
);

const isSwitching = ref(false);

const isTeoriView = computed(() => sessionInfo.value?.view_session === 'teori');

const handleSwitchView = (target: 'teori' | 'bengkel') => {
    if (sessionInfo.value?.view_session === target) return;

    isSwitching.value = true;
    router.post(
        '/session/switch',
        { session: target },
        {
            preserveScroll: true,
            onSuccess: () => {
                const label = target === 'teori' ? 'Sesi Teori' : 'Sesi Bengkel';
                toast.success(`Halaman beralih ke tampilan ${label}. Masa berlaku sesi resmi tetap aman! ✨`);
            },
            onFinish: () => {
                isSwitching.value = false;
            },
        }
    );
};

const handleExtend = (days: number = 7) => {
    isSwitching.value = true;
    router.post(
        '/session/extend',
        { days },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Masa aktif sesi resmi berhasil diperpanjang ${days} hari! 📅`);
            },
            onFinish: () => {
                isSwitching.value = false;
            },
        }
    );
};

const isCycleModalOpen = ref(false);
const targetCycle = ref<'teori' | 'bengkel'>('teori');

const openCycleConfirm = (target: 'teori' | 'bengkel') => {
    targetCycle.value = target;
    isCycleModalOpen.value = true;
};

const handleConfirmCycle = () => {
    const target = targetCycle.value;
    const label = target === 'teori' ? 'Sesi Teori' : 'Sesi Bengkel';
    isSwitching.value = true;
    router.post(
        '/session/start-cycle',
        { session: target },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Siklus resmi baru ${label} telah dimulai (1 bulan)! 🚀`);
                isCycleModalOpen.value = false;
            },
            onFinish: () => {
                isSwitching.value = false;
            },
        },
    );
};
</script>

<template>
    <DropdownMenu v-if="sessionInfo">
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                :disabled="isSwitching"
                class="flex items-center gap-2 rounded-xl px-2.5 py-1.5 text-xs font-semibold transition-all border shadow-2xs cursor-pointer select-none"
                :class="[
                    isTeoriView
                        ? 'bg-indigo-50/80 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/80 hover:bg-indigo-100 dark:hover:bg-indigo-900/60'
                        : 'bg-amber-50/80 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800/80 hover:bg-amber-100 dark:hover:bg-amber-900/60'
                ]"
            >
                <span class="flex size-5 items-center justify-center rounded-md" :class="isTeoriView ? 'bg-indigo-600 text-white' : 'bg-amber-500 text-white'">
                    <BookOpen v-if="isTeoriView" class="size-3 stroke-[2.5]" />
                    <Wrench v-else class="size-3 stroke-[2.5]" />
                </span>

                <div class="flex items-center gap-1.5 text-left">
                    <span>{{ sessionInfo.label }}</span>
                    <span v-if="!sessionInfo.is_viewing_official" class="text-[9px] px-1.5 py-0.2 rounded-full font-bold uppercase" :class="isTeoriView ? 'bg-indigo-200/70 text-indigo-800 dark:bg-indigo-900' : 'bg-amber-200/70 text-amber-900 dark:bg-amber-900'">
                        Lihat
                    </span>
                    <span class="hidden sm:inline text-[10px] opacity-75 font-normal">({{ sessionInfo.days_remaining }}h)</span>
                </div>

                <ChevronDown class="size-3 opacity-60 ml-0.5" />
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            class="w-80 rounded-2xl bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 p-2.5 shadow-xl"
        >
            <!-- Header Info -->
            <DropdownMenuLabel class="p-2 font-normal">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                :class="isTeoriView ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300' : 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300'"
                            >
                                Menampilkan: {{ sessionInfo.label }}
                            </span>
                        </div>
                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-100 mt-1">
                            {{ sessionInfo.subtitle }}
                        </div>
                    </div>
                </div>

                <!-- Periode Sesi Resmi Box -->
                <div class="mt-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 p-2.5 border border-slate-100 dark:border-slate-800 text-xs space-y-1.5">
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[11px]">
                        <span class="flex items-center gap-1 font-semibold text-slate-700 dark:text-slate-300">
                            <Calendar class="size-3 text-indigo-500 dark:text-indigo-400" />
                            Sesi Resmi: {{ sessionInfo.official_label }}
                        </span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">
                            {{ sessionInfo.days_remaining }} hari lagi
                        </span>
                    </div>

                    <div class="font-medium text-slate-800 dark:text-slate-200 text-xs">
                        {{ sessionInfo.period_range }}
                    </div>

                    <div class="text-[10px] text-slate-500 dark:text-slate-400 pt-0.5 border-t border-slate-200/50 dark:border-slate-700/50 flex items-center gap-1">
                        <Sparkles class="size-3 text-emerald-500 shrink-0" />
                        <span>Masa berlaku resmi tetap aman & tidak ter-reset saat melihat halaman lain.</span>
                    </div>
                </div>
            </DropdownMenuLabel>

            <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800 my-1" />

            <!-- Pilihan Tampilan Halaman -->
            <div class="px-2 pt-1 pb-1">
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                    Pilih Tampilan Halaman:
                </span>
            </div>

            <DropdownMenuGroup class="space-y-1 p-1">
                <!-- Tampilkan Sesi Teori -->
                <DropdownMenuItem
                    @click="handleSwitchView('teori')"
                    class="flex items-center justify-between rounded-xl px-2.5 py-2 text-xs font-medium cursor-pointer"
                    :class="isTeoriView ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-semibold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="flex size-7 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                            <BookOpen class="size-3.5" />
                        </div>
                        <div class="flex flex-col">
                            <span>Halaman Sesi Teori</span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500">Mata pelajaran umum & akademik</span>
                        </div>
                    </div>
                    <Check v-if="isTeoriView" class="size-4 text-indigo-600 dark:text-indigo-400" />
                </DropdownMenuItem>

                <!-- Tampilkan Sesi Bengkel -->
                <DropdownMenuItem
                    @click="handleSwitchView('bengkel')"
                    class="flex items-center justify-between rounded-xl px-2.5 py-2 text-xs font-medium cursor-pointer"
                    :class="!isTeoriView ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 font-semibold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="flex size-7 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">
                            <Wrench class="size-3.5" />
                        </div>
                        <div class="flex flex-col">
                            <span>Halaman Sesi Bengkel</span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500">Praktik kejuruan & workshop</span>
                        </div>
                    </div>
                    <Check v-if="!isTeoriView" class="size-4 text-amber-600 dark:text-amber-400" />
                </DropdownMenuItem>
            </DropdownMenuGroup>

            <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800 my-1" />

            <!-- Aksi Sesi Resmi -->
            <DropdownMenuGroup class="space-y-1 p-1">
                <!-- Perpanjang Sesi Resmi (+1 Minggu) -->
                <DropdownMenuItem
                    @click="handleExtend(7)"
                    class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                >
                    <div class="flex size-7 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                        <PlusCircle class="size-3.5" />
                    </div>
                    <div class="flex flex-col">
                        <span class="font-semibold">Perpanjang Sesi Resmi (+1 Minggu)</span>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500">Tambah 7 hari masa aktif {{ sessionInfo.official_label }}</span>
                    </div>
                </DropdownMenuItem>

                <!-- Mulai Siklus Resmi 1 Bulan Baru -->
                <DropdownMenuItem
                    @click="openCycleConfirm(isTeoriView ? 'bengkel' : 'teori')"
                    class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-700 dark:hover:text-rose-300 cursor-pointer"
                >
                    <div class="flex size-7 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500">
                        <RotateCw class="size-3.5" />
                    </div>
                    <div class="flex flex-col">
                        <span class="font-medium">Mulai Siklus Resmi 1 Bulan Baru...</span>
                        <span class="text-[10px] text-slate-400">Reset tanggal siklus resmi 1 bulan dari hari ini</span>
                    </div>
                </DropdownMenuItem>
            </DropdownMenuGroup>
        </DropdownMenuContent>
    </DropdownMenu>

    <!-- Modal Konfirmasi Mulai Siklus Baru -->
    <DeleteConfirmModal
        :open="isCycleModalOpen"
        @update:open="isCycleModalOpen = $event"
        title="Mulai Siklus Resmi Baru"
        :description="`Apakah Anda ingin memulai siklus resmi baru untuk ${targetCycle === 'teori' ? 'Sesi Teori' : 'Sesi Bengkel'} mulai dari hari ini (durasi 1 bulan)?`"
        confirm-text="Ya, Mulai Siklus"
        :is-destructive="false"
        :loading="isSwitching"
        @confirm="handleConfirmCycle"
    />
</template>
