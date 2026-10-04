<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSchoolSession } from '@/composables/useSchoolSession';

const { isTeori, sessionInfo } = useSchoolSession();
</script>

<template>
    <div class="flex items-center gap-2.5">
        <div
            class="flex size-9 items-center justify-center rounded-xl text-white shadow-sm transition-all duration-300"
            :class="isTeori ? 'bg-indigo-600 shadow-indigo-500/25' : 'bg-amber-600 shadow-amber-500/25'"
        >
            <AppLogoIcon class="size-5" />
        </div>
        <div class="grid text-left">
            <span class="text-base font-bold tracking-tight text-slate-900 dark:text-white leading-none">
                Study<span class="transition-colors duration-300" :class="isTeori ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600 dark:text-amber-400'">Hub</span>
            </span>
            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 leading-tight">
                {{ sessionInfo?.label || 'Student Productivity' }}
            </span>
        </div>
    </div>
</template>
