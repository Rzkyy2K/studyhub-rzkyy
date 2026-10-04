<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Calendar, CheckCircle2, Clock } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative min-h-screen grid lg:grid-cols-2 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased"
    >
        <!-- Left Side: Branding & Student Illustration Decor -->
        <div
            class="relative hidden h-full flex-col justify-between overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-900 p-12 text-white lg:flex"
        >
            <!-- Background glow & subtle patterns -->
            <div class="absolute -top-24 -left-24 size-96 rounded-full bg-white/10 blur-3xl pointer-events-none" />
            <div class="absolute -bottom-24 -right-24 size-96 rounded-full bg-indigo-500/20 blur-3xl pointer-events-none" />

            <!-- Brand Logo -->
            <div class="relative z-10">
                <Link
                    :href="home()"
                    class="inline-flex items-center gap-3 text-2xl font-bold tracking-tight"
                >
                    <div class="flex size-10 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-md">
                        <AppLogoIcon class="size-6" />
                    </div>
                    <span>Study<span class="text-indigo-200">Hub</span></span>
                </Link>
                <p class="mt-2 text-sm text-indigo-100 max-w-sm">
                    Aplikasi produktivitas pribadi untuk pelajar cerdas & terorganisir.
                </p>
            </div>

            <!-- Student Value Highlights -->
            <div class="relative z-10 my-auto space-y-6 max-w-md">
                <div class="space-y-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur-sm">
                        ✨ Teman Belajar Harianmu
                    </span>
                    <h2 class="text-3xl font-extrabold tracking-tight text-white leading-tight">
                        Fokus pada apa yang penting hari ini.
                    </h2>
                    <p class="text-sm text-indigo-100">
                        Atur tugas sekolah, lihat jadwal pelajaran mingguan, dan selesaikan aktivitas harian tanpa khawatir tertinggal deadline.
                    </p>
                </div>

                <div class="space-y-3 pt-2">
                    <div class="flex items-center gap-3 rounded-xl bg-white/10 p-3 backdrop-blur-xs border border-white/10">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-400/20 text-emerald-300">
                            <CheckCircle2 class="size-4.5" />
                        </div>
                        <div class="text-xs">
                            <span class="font-semibold block text-white">Deadline Tugas Terpantau</span>
                            <span class="text-indigo-200">Indikator visual urgency membantu memprioritaskan tugas terdekat</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-xl bg-white/10 p-3 backdrop-blur-xs border border-white/10">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-400/20 text-amber-300">
                            <Calendar class="size-4.5" />
                        </div>
                        <div class="text-xs">
                            <span class="font-semibold block text-white">Jadwal Pelajaran Rapi</span>
                            <span class="text-indigo-200">Ketahui guru pengajar, jam mulai, dan ruangan kelas</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-xl bg-white/10 p-3 backdrop-blur-xs border border-white/10">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-indigo-400/20 text-indigo-200">
                            <Clock class="size-4.5" />
                        </div>
                        <div class="text-xs">
                            <span class="font-semibold block text-white">To-Do Harian Personal</span>
                            <span class="text-indigo-200">Checklist ringan untuk memantau progres belajar mandiri</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="relative z-10 text-xs text-indigo-200">
                &copy; {{ new Date().getFullYear() }} StudyHub. Dibuat untuk kenyamanan belajar setiap hari.
            </div>
        </div>

        <!-- Right Side: Auth Form Container -->
        <div class="relative flex flex-col justify-center px-6 py-10 sm:px-12 lg:px-16">
            <!-- Top Right Theme Toggle -->
            <div class="absolute top-6 right-6 z-20">
                <ThemeToggle />
            </div>

            <!-- Mobile Brand Logo -->
            <div class="mb-8 flex items-center justify-center lg:hidden">
                <Link :href="home()" class="inline-flex items-center gap-2.5">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm">
                        <AppLogoIcon class="size-5" />
                    </div>
                    <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">Study<span class="text-indigo-600 dark:text-indigo-400">Hub</span></span>
                </Link>
            </div>

            <div class="mx-auto w-full max-w-md space-y-6">
                <div class="text-center sm:text-left space-y-1.5">
                    <h1 v-if="title" class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-slate-500 dark:text-slate-400">
                        {{ description }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-sm">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
