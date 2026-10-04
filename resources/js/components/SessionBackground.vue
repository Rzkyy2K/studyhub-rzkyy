<script setup lang="ts">
import { BookOpen, Wrench } from '@lucide/vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        session?: 'teori' | 'bengkel' | string;
    }>(),
    {
        session: 'teori',
    }
);

const isTeori = computed(() => props.session !== 'bengkel');
</script>

<template>
    <div
        class="pointer-events-none fixed inset-0 z-0 overflow-hidden select-none transition-colors duration-700 ease-in-out"
        aria-hidden="true"
    >
        <!-- Base Atmospheric Tint: Distinct, rich tinted canvas in light mode (anti-plain white) & deep in dark mode -->
        <div
            class="absolute inset-0 transition-all duration-700 ease-in-out pointer-events-none"
            :class="[
                isTeori
                    ? 'bg-gradient-to-br from-[#dbe7f7] via-[#e4edf8] to-[#d7e5f6] dark:from-[#060c19] dark:via-[#081326] dark:to-[#060e1d]'
                    : 'bg-gradient-to-br from-[#ede0ce] via-[#f5ebdd] to-[#ebdcc8] dark:from-[#110e0a] dark:via-[#19140c] dark:to-[#140f09]'
            ]"
        />

        <!-- Top Glow Gradient Accent Line -->
        <div
            class="absolute top-0 inset-x-0 h-[3px] transition-all duration-700 ease-in-out z-10"
            :class="[
                isTeori
                    ? 'bg-gradient-to-r from-indigo-500 via-sky-400 to-indigo-600 shadow-[0_2px_16px_rgba(99,102,241,0.5)]'
                    : 'bg-gradient-to-r from-amber-500 via-emerald-400 to-orange-500 shadow-[0_2px_16px_rgba(245,158,11,0.5)]'
            ]"
        />

        <!-- Ambient Glow Orb 1 (Top Corner Hub) -->
        <div
            class="absolute rounded-full blur-[110px] transition-all duration-700 ease-out pointer-events-none"
            :class="[
                isTeori
                    ? '-top-28 -left-28 w-[640px] h-[640px] bg-indigo-500/28 dark:bg-indigo-600/22'
                    : '-top-28 -right-28 w-[640px] h-[640px] bg-amber-500/32 dark:bg-amber-600/22'
            ]"
        />

        <!-- Ambient Glow Orb 2 (Opposite Corner Hub) -->
        <div
            class="absolute rounded-full blur-[120px] transition-all duration-700 ease-out pointer-events-none"
            :class="[
                isTeori
                    ? '-bottom-28 -right-28 w-[600px] h-[600px] bg-sky-400/30 dark:bg-sky-500/18'
                    : '-bottom-28 -left-28 w-[600px] h-[600px] bg-emerald-500/28 dark:bg-emerald-600/18'
            ]"
        />

        <!-- Ambient Glow Orb 3 (Secondary Floating Accent) -->
        <div
            class="absolute rounded-full blur-[125px] transition-all duration-700 ease-out pointer-events-none"
            :class="[
                isTeori
                    ? 'top-1/4 right-1/4 w-[500px] h-[500px] bg-blue-400/24 dark:bg-violet-600/16'
                    : 'top-1/4 left-1/4 w-[500px] h-[500px] bg-orange-400/26 dark:bg-orange-600/16'
            ]"
        />

        <!-- Ambient Glow Orb 4 (Center Underlay Glow) -->
        <div
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full blur-[140px] transition-all duration-700 ease-out pointer-events-none"
            :class="[
                isTeori
                    ? 'w-[750px] h-[500px] bg-indigo-400/20 dark:bg-indigo-500/12'
                    : 'w-[750px] h-[500px] bg-amber-400/22 dark:bg-amber-500/12'
            ]"
        />

        <!-- Subtle Dot Matrix Pattern: Crisp tech dots visible in both light & dark modes -->
        <div
            class="absolute inset-0 transition-opacity duration-700 pointer-events-none"
            :style="{
                backgroundImage: isTeori
                    ? 'radial-gradient(circle, rgba(79, 70, 229, 0.22) 1.4px, transparent 1.4px)'
                    : 'radial-gradient(circle, rgba(217, 119, 6, 0.25) 1.4px, transparent 1.4px)',
                backgroundSize: '24px 24px',
            }"
            :class="isTeori ? 'opacity-90 dark:opacity-40' : 'opacity-95 dark:opacity-45'"
        />

        <!-- Scholify-Style Concentric Orbit Rings SVG (Teori: Academic Orbit, Bengkel: Technical Blueprint) -->
        <div class="absolute inset-0 transition-all duration-700 ease-in-out pointer-events-none">
            <!-- TEORI: Academic Coordinate & Orbit Rings -->
            <transition name="fade">
                <svg
                    v-if="isTeori"
                    class="h-full w-full opacity-90 dark:opacity-50"
                    viewBox="0 0 1000 1000"
                    fill="none"
                    preserveAspectRatio="none"
                >
                    <!-- Center Core Target Rings (cx=200, cy=180) -->
                    <circle cx="200" cy="180" r="14" stroke="currentColor" stroke-width="1.8" class="text-indigo-600/60 dark:text-indigo-400/40" />
                    <circle cx="200" cy="180" r="40" stroke="currentColor" stroke-width="1.2" class="text-indigo-500/40 dark:text-indigo-400/25" />
                    <circle cx="200" cy="180" r="140" stroke="currentColor" stroke-width="1.4" stroke-dasharray="4 6" class="text-indigo-600/45 dark:text-indigo-400/30" />
                    <circle cx="200" cy="180" r="280" stroke="currentColor" stroke-width="1.6" class="text-indigo-600/40 dark:text-indigo-400/25" />
                    <circle cx="200" cy="180" r="420" stroke="currentColor" stroke-width="1.6" stroke-dasharray="8 8" class="text-sky-600/45 dark:text-sky-400/30" />
                    <circle cx="200" cy="180" r="580" stroke="currentColor" stroke-width="1.3" class="text-indigo-600/30 dark:text-indigo-400/20" />
                    <circle cx="200" cy="180" r="760" stroke="currentColor" stroke-width="1.6" stroke-dasharray="12 6 4 6" class="text-blue-600/35 dark:text-blue-400/25" />
                    <circle cx="200" cy="180" r="980" stroke="currentColor" stroke-width="1.2" stroke-dasharray="6 10" class="text-indigo-600/25 dark:text-indigo-400/15" />

                    <!-- Coordinate Axis Lines -->
                    <line x1="0" y1="180" x2="1000" y2="180" stroke="currentColor" stroke-width="1" stroke-dasharray="4 8" class="text-indigo-500/25 dark:text-indigo-400/15" />
                    <line x1="200" y1="0" x2="200" y2="1000" stroke="currentColor" stroke-width="1" stroke-dasharray="4 8" class="text-indigo-500/25 dark:text-indigo-400/15" />
                    <line x1="0" y1="0" x2="800" y2="720" stroke="currentColor" stroke-width="0.9" stroke-dasharray="6 10" class="text-sky-500/25 dark:text-sky-400/15" />

                    <!-- Coordinate Intersect Markers (+) -->
                    <path d="M 200 30 L 200 50 M 190 40 L 210 40" stroke="currentColor" stroke-width="1.4" class="text-indigo-600/50 dark:text-indigo-400/35" />
                    <path d="M 610 180 L 630 180 M 620 170 L 620 190" stroke="currentColor" stroke-width="1.4" class="text-indigo-600/50 dark:text-indigo-400/35" />
                    <path d="M 200 590 L 200 610 M 190 600 L 210 600" stroke="currentColor" stroke-width="1.4" class="text-indigo-600/50 dark:text-indigo-400/35" />

                    <!-- Bottom-Right Echo Rings (cx=920, cy=860) -->
                    <circle cx="920" cy="860" r="220" stroke="currentColor" stroke-width="1.4" stroke-dasharray="6 8" class="text-sky-600/40 dark:text-sky-400/25" />
                    <circle cx="920" cy="860" r="380" stroke="currentColor" stroke-width="1.2" class="text-indigo-600/30 dark:text-indigo-400/20" />
                    <circle cx="920" cy="860" r="560" stroke="currentColor" stroke-width="1.4" stroke-dasharray="10 8" class="text-blue-600/25 dark:text-blue-400/15" />
                </svg>

                <!-- BENGKEL: Precision Blueprint & Technical Coordinate Rings -->
                <svg
                    v-else
                    class="h-full w-full opacity-90 dark:opacity-50"
                    viewBox="0 0 1000 1000"
                    fill="none"
                    preserveAspectRatio="none"
                >
                    <!-- Main Technical Blueprint Rings (cx=800, cy=180) -->
                    <circle cx="800" cy="180" r="14" stroke="currentColor" stroke-width="1.8" class="text-amber-600/60 dark:text-amber-400/40" />
                    <circle cx="800" cy="180" r="40" stroke="currentColor" stroke-width="1.2" class="text-amber-500/40 dark:text-amber-400/25" />
                    <circle cx="800" cy="180" r="140" stroke="currentColor" stroke-width="1.6" stroke-dasharray="8 6" class="text-amber-600/50 dark:text-amber-400/35" />
                    <circle cx="800" cy="180" r="280" stroke="currentColor" stroke-width="1.4" class="text-amber-600/40 dark:text-amber-400/25" />
                    <circle cx="800" cy="180" r="420" stroke="currentColor" stroke-width="1.6" stroke-dasharray="12 4 4 4" class="text-emerald-700/45 dark:text-emerald-400/30" />
                    <circle cx="800" cy="180" r="580" stroke="currentColor" stroke-width="1.3" class="text-amber-600/30 dark:text-amber-400/20" />
                    <circle cx="800" cy="180" r="760" stroke="currentColor" stroke-width="1.6" stroke-dasharray="14 6 4 6" class="text-orange-600/35 dark:text-orange-400/25" />
                    <circle cx="800" cy="180" r="980" stroke="currentColor" stroke-width="1.2" stroke-dasharray="6 8" class="text-amber-600/25 dark:text-amber-400/15" />

                    <!-- Technical Axis Lines -->
                    <line x1="0" y1="180" x2="1000" y2="180" stroke="currentColor" stroke-width="1" stroke-dasharray="4 8" class="text-amber-600/25 dark:text-amber-400/15" />
                    <line x1="800" y1="0" x2="800" y2="1000" stroke="currentColor" stroke-width="1" stroke-dasharray="4 8" class="text-amber-600/25 dark:text-amber-400/15" />
                    <line x1="1000" y1="0" x2="200" y2="720" stroke="currentColor" stroke-width="0.9" stroke-dasharray="6 10" class="text-emerald-600/25 dark:text-emerald-400/15" />

                    <!-- Technical Intersect Markers (+) -->
                    <path d="M 800 30 L 800 50 M 790 40 L 810 40" stroke="currentColor" stroke-width="1.4" class="text-amber-600/50 dark:text-amber-400/35" />
                    <path d="M 370 180 L 390 180 M 380 170 L 380 190" stroke="currentColor" stroke-width="1.4" class="text-amber-600/50 dark:text-amber-400/35" />
                    <path d="M 800 590 L 800 610 M 790 600 L 810 600" stroke="currentColor" stroke-width="1.4" class="text-amber-600/50 dark:text-amber-400/35" />

                    <!-- Bottom-Left Echo Rings (cx=100, cy=860) -->
                    <circle cx="100" cy="860" r="220" stroke="currentColor" stroke-width="1.4" stroke-dasharray="8 6" class="text-emerald-700/40 dark:text-emerald-400/25" />
                    <circle cx="100" cy="860" r="380" stroke="currentColor" stroke-width="1.2" class="text-amber-600/30 dark:text-amber-400/20" />
                    <circle cx="100" cy="860" r="560" stroke="currentColor" stroke-width="1.4" stroke-dasharray="10 8" class="text-orange-600/25 dark:text-orange-400/15" />
                </svg>
            </transition>
        </div>

        <!-- Thematic Corner Watermark -->
        <div class="absolute bottom-0 right-0 p-6 pointer-events-none transition-all duration-700 ease-in-out">
            <BookOpen
                v-if="isTeori"
                class="size-56 -bottom-10 -right-10 absolute text-indigo-700/[0.07] dark:text-indigo-400/[0.06] stroke-[1]"
            />
            <Wrench
                v-else
                class="size-56 -bottom-10 -right-10 absolute text-amber-700/[0.08] dark:text-amber-400/[0.06] stroke-[1]"
            />
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
