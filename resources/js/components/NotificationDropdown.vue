<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Bell,
    CalendarDays,
    CheckCheck,
    CheckCircle2,
    Clock,
    ListTodo,
    Settings,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import DeleteConfirmModal from '@/components/DeleteConfirmModal.vue';
import type { UserNotification } from '@/types';

const page = usePage();
const isOpen = ref(false);
const activeTab = ref<'all' | 'unread'>('all');

const notifications = computed<UserNotification[]>(() => {
    return (page.props.notifications as UserNotification[]) || [];
});

const unreadCount = computed<number>(() => {
    return (page.props.unread_notifications_count as number) || 0;
});

const filteredNotifications = computed(() => {
    if (activeTab.value === 'unread') {
        return notifications.value.filter((n) => !n.is_read);
    }
    return notifications.value;
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};

// Close on ESC
const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && isOpen.value) {
        closeDropdown();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

// Actions
const markAsRead = (notif: UserNotification) => {
    if (notif.action_url) {
        closeDropdown();
        if (!notif.is_read) {
            router.patch(`/notifications/${notif.id}/read`, {}, {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    router.visit(notif.action_url!);
                },
            });
        } else {
            router.visit(notif.action_url);
        }
        return;
    }

    if (!notif.is_read) {
        router.patch(`/notifications/${notif.id}/read`, {}, {
            preserveScroll: true,
            preserveState: true,
        });
    }
};

const markAllAsRead = () => {
    router.post('/notifications/mark-all-read', {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success('Semua notifikasi ditandai sudah dibaca.');
        },
    });
};

const deleteNotification = (notif: UserNotification, e: Event) => {
    e.stopPropagation();
    router.delete(`/notifications/${notif.id}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success('Notifikasi dihapus.');
        },
    });
};

const isClearModalOpen = ref(false);
const isClearing = ref(false);

const openClearConfirm = () => {
    isClearModalOpen.value = true;
};

const handleClearAll = () => {
    isClearing.value = true;
    router.delete('/notifications', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success('Semua notifikasi telah dibersihkan.');
            isClearModalOpen.value = false;
        },
        onFinish: () => {
            isClearing.value = false;
        },
    });
};

// Icon & style per type
const getIcon = (type: string) => {
    switch (type) {
        case 'danger':
            return AlertCircle;
        case 'warning':
            return Clock;
        case 'success':
            return CheckCircle2;
        default:
            return CalendarDays;
    }
};

const getIconStyle = (type: string) => {
    switch (type) {
        case 'danger':
            return 'bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400';
        case 'warning':
            return 'bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400';
        case 'success':
            return 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400';
        default:
            return 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400';
    }
};
</script>

<template>
    <div class="relative">
        <!-- Trigger Bell Button -->
        <button
            type="button"
            @click="toggleDropdown"
            title="Notifikasi"
            aria-label="Lihat Notifikasi"
            class="relative flex size-9 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500/20 shadow-xs"
            :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white': isOpen }"
        >
            <Bell class="size-4.5" />

            <!-- Unread badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 flex min-w-4.5 h-4.5 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-slate-900 animate-in zoom-in-75 duration-200"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Menu / Popover -->
        <div
            v-if="isOpen"
            class="fixed inset-x-4 top-16 z-50 sm:absolute sm:inset-auto sm:right-0 sm:top-full sm:mt-2 sm:w-96 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl animate-in fade-in zoom-in-95 duration-150 overflow-hidden"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 p-3.5 bg-slate-50/70 dark:bg-slate-800/60">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-sm text-slate-900 dark:text-white">Notifikasi</span>
                    <span
                        v-if="unreadCount > 0"
                        class="rounded-full bg-rose-100 dark:bg-rose-950/60 px-2 py-0.5 text-[11px] font-semibold text-rose-700 dark:text-rose-400"
                    >
                        {{ unreadCount }} baru
                    </span>
                </div>

                <div class="flex items-center gap-1">
                    <button
                        v-if="unreadCount > 0"
                        type="button"
                        @click="markAllAsRead"
                        title="Tandai semua dibaca"
                        class="flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition-colors"
                    >
                        <CheckCheck class="size-3.5" />
                        <span>Baca Semua</span>
                    </button>

                    <button
                        v-if="notifications.length > 0"
                        type="button"
                        @click="openClearConfirm"
                        title="Bersihkan semua"
                        class="rounded-md p-1 text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 dark:hover:text-rose-400 transition-colors"
                    >
                        <Trash2 class="size-3.5" />
                    </button>

                    <button
                        type="button"
                        @click="closeDropdown"
                        class="rounded-md p-1 text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-600 dark:hover:text-slate-200 transition-colors sm:hidden"
                    >
                        <X class="size-4" />
                    </button>
                </div>
            </div>

            <!-- Tabs: Semua vs Belum Dibaca -->
            <div class="flex border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 pt-1">
                <button
                    type="button"
                    @click="activeTab = 'all'"
                    class="border-b-2 py-2 px-3 text-xs font-medium transition-colors"
                    :class="activeTab === 'all' ? 'border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 font-semibold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
                >
                    Semua ({{ notifications.length }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'unread'"
                    class="border-b-2 py-2 px-3 text-xs font-medium transition-colors"
                    :class="activeTab === 'unread' ? 'border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400 font-semibold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
                >
                    Belum Dibaca ({{ unreadCount }})
                </button>
            </div>

            <!-- List Notifications -->
            <div class="max-h-[380px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                <div
                    v-for="item in filteredNotifications"
                    :key="item.id"
                    @click="markAsRead(item)"
                    class="group relative flex items-start gap-3 p-3.5 transition-colors cursor-pointer"
                    :class="item.is_read ? 'bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/60' : 'bg-indigo-50/30 dark:bg-indigo-950/30 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/50'"
                >
                    <!-- Type Icon -->
                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-lg mt-0.5"
                        :class="getIconStyle(item.type)"
                    >
                        <component :is="getIcon(item.type)" class="size-4" />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0 pr-4">
                        <div class="flex items-center gap-1.5">
                            <span
                                class="text-xs font-semibold text-slate-900 dark:text-slate-100 leading-snug line-clamp-1"
                                :class="{ 'font-bold text-indigo-950 dark:text-indigo-200': !item.is_read }"
                            >
                                {{ item.title }}
                            </span>
                            <!-- Unread dot -->
                            <span
                                v-if="!item.is_read"
                                class="size-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400"
                            ></span>
                        </div>

                        <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed line-clamp-2">
                            {{ item.message }}
                        </p>

                        <div class="mt-1 flex items-center gap-2 text-[10px] text-slate-400 dark:text-slate-500">
                            <span>{{ item.time_ago }}</span>
                            <span v-if="item.action_url" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
                                Buka link →
                            </span>
                        </div>
                    </div>

                    <!-- Single Delete button -->
                    <button
                        type="button"
                        @click="deleteNotification(item, $event)"
                        title="Hapus"
                        class="opacity-0 group-hover:opacity-100 rounded p-1 text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 dark:hover:text-rose-400 transition-opacity absolute right-2 top-2"
                    >
                        <Trash2 class="size-3.5" />
                    </button>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredNotifications.length === 0"
                    class="py-10 px-4 text-center space-y-2"
                >
                    <div class="mx-auto flex size-10 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        <CheckCircle2 class="size-5" />
                    </div>
                    <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                        {{ activeTab === 'unread' ? 'Semua notifikasi telah dibaca' : 'Belum ada notifikasi baru 🎉' }}
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 max-w-xs mx-auto">
                        Notifikasi jadwal, tugas sekolah, dan pengingat deadline akan muncul otomatis di sini.
                    </p>
                </div>

                <!-- Footer with Link to Notification Settings -->
                <div class="border-t border-slate-200/70 p-2.5 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-900/50 text-center">
                    <Link
                        href="/settings/notifications"
                        @click="closeDropdown"
                        class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1.5"
                    >
                        <Settings class="size-3" />
                        Atur Notifikasi Layar HP & WhatsApp
                    </Link>
                </div>
            </div>
        </div>

        <!-- Backdrop overlay when open on mobile or outside click -->
        <div
            v-if="isOpen"
            @click="closeDropdown"
            class="fixed inset-0 z-40 bg-black/20 backdrop-blur-2xs sm:bg-transparent"
        />

        <!-- Clear All Notifications Modal -->
        <DeleteConfirmModal
            :open="isClearModalOpen"
            @update:open="isClearModalOpen = $event"
            title="Bersihkan Semua Notifikasi"
            description="Apakah Anda yakin ingin membersihkan dan menghapus semua notifikasi dari daftar?"
            confirm-text="Ya, Bersihkan Semua"
            :loading="isClearing"
            @confirm="handleClearAll"
        />
    </div>
</template>
