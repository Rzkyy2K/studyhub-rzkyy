<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    const nextTheme = resolvedAppearance.value === 'dark' ? 'light' : 'dark';
    updateAppearance(nextTheme);
};
</script>

<template>
    <button
        type="button"
        @click="toggleTheme"
        :title="resolvedAppearance === 'dark' ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
        :aria-label="resolvedAppearance === 'dark' ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
        class="relative flex size-9 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500/20 shadow-xs"
    >
        <!-- Sun icon (visible in dark mode) -->
        <Sun
            v-if="resolvedAppearance === 'dark'"
            class="size-4.5 text-amber-400 animate-in spin-in-90 zoom-in-75 duration-200"
        />

        <!-- Moon icon (visible in light mode) -->
        <Moon
            v-else
            class="size-4.5 text-indigo-600 animate-in -spin-in-90 zoom-in-75 duration-200"
        />
    </button>
</template>
