<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    Bell,
    CheckCircle2,
    Clock,
    HelpCircle,
    Info,
    Laptop,
    MessageCircle,
    Phone,
    Send,
    ShieldAlert,
    Smartphone,
    Trash2,
    XCircle,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Subscription = {
    id: number;
    device_name: string;
    created_at: string;
};

type UserData = {
    id: number;
    name: string;
    phone_number: string | null;
    wa_notifications_enabled: boolean;
    push_notifications_enabled: boolean;
    notify_h_minus_1: boolean;
    notify_due_today: boolean;
    notify_overdue: boolean;
};

const props = defineProps<{
    vapidPublicKey: string | null;
    subscriptions: Subscription[];
    waProvider: string;
    waConfigured: boolean;
    user: UserData;
}>();

// Web Push Client State
const isSupported = ref(false);
const permissionState = ref<'default' | 'granted' | 'denied'>('default');
const isSubscribing = ref(false);
const isTestingPush = ref(false);
const isTestingWA = ref(false);
const currentDeviceSubscribed = ref(false);

// Preferences Form
const form = useForm({
    phone_number: props.user.phone_number || '',
    wa_notifications_enabled: props.user.wa_notifications_enabled,
    push_notifications_enabled: props.user.push_notifications_enabled,
    notify_h_minus_1: props.user.notify_h_minus_1,
    notify_due_today: props.user.notify_due_today,
    notify_overdue: props.user.notify_overdue,
});

// Convert base64 VAPID public key to Uint8Array for browser push manager
function urlBase64ToUint8Array(base64String: string) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Detect device name
function getDeviceName(): string {
    const ua = navigator.userAgent;
    if (/android/i.test(ua)) return 'Android Device (Chrome/PWA)';
    if (/iPad|iPhone|iPod/.test(ua)) return 'iOS Device (Safari/PWA)';
    if (/Windows/i.test(ua)) return 'Windows PC (' + (ua.includes('Edg') ? 'Edge' : 'Chrome') + ')';
    if (/Macintosh/i.test(ua)) return 'Mac (' + (ua.includes('Safari') && !ua.includes('Chrome') ? 'Safari' : 'Chrome') + ')';
    return 'Web Browser Device';
}

async function checkPushStatus() {
    if (typeof window === 'undefined') return;

    isSupported.value = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    if (!isSupported.value) return;

    permissionState.value = Notification.permission;

    try {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        currentDeviceSubscribed.value = !!sub;
    } catch (e) {
        console.warn('Error checking push subscription:', e);
    }
}

onMounted(() => {
    checkPushStatus();
});

// Subscribe to Web Push
async function subscribeToPush() {
    if (!isSupported.value) {
        alert('Browser atau perangkat ini belum mendukung Web Push Notifications.');
        return;
    }

    if (!props.vapidPublicKey) {
        alert('Kunci VAPID belum dikonfigurasi di server.');
        return;
    }

    isSubscribing.value = true;
    try {
        const permission = await Notification.requestPermission();
        permissionState.value = permission;

        if (permission !== 'granted') {
            toast.warning('Izin notifikasi ditolak pada browser Anda.');
            isSubscribing.value = false;
            return;
        }

        const reg = await navigator.serviceWorker.ready;
        let sub = await reg.pushManager.getSubscription();

        if (!sub) {
            const applicationServerKey = urlBase64ToUint8Array(props.vapidPublicKey);
            sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey,
            });
        }

        const subJson = sub.toJSON();

        // Send to backend
        const response = await fetch('/settings/notifications/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
            body: JSON.stringify({
                endpoint: sub.endpoint,
                keys: subJson.keys,
                deviceName: getDeviceName(),
                contentEncoding: (subJson as any).contentEncoding || 'aesgcm',
            }),
        });

        if (response.ok) {
            currentDeviceSubscribed.value = true;
            toast.success('Notifikasi berhasil diaktifkan di perangkat ini!');
            router.reload({ only: ['subscriptions'] });
        } else {
            toast.error('Gagal menyimpan langganan perangkat ke server.');
        }
    } catch (err: any) {
        console.error('Failed to subscribe to push:', err);
        toast.error(err.message || 'Gagal mengaktifkan notifikasi.');
    } finally {
        isSubscribing.value = false;
    }
}

// Test Push Notification
async function sendTestPush() {
    isTestingPush.value = true;
    try {
        const res = await fetch('/settings/notifications/test-push', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
        });
        const data = await res.json();
        if (res.ok && data.success) {
            toast.success(data.message || 'Notifikasi uji coba berhasil dikirim! 🔔');
        } else {
            toast.warning(data.message || 'Gagal mengirim notifikasi uji coba.');
        }
    } catch (e: any) {
        toast.error(e.message || 'Koneksi gagal.');
    } finally {
        isTestingPush.value = false;
    }
}

// Test WhatsApp Message
async function sendTestWA() {
    if (!form.phone_number) {
        toast.warning('Silakan isi nomor WhatsApp terlebih dahulu.');
        return;
    }

    isTestingWA.value = true;
    try {
        const res = await fetch('/settings/notifications/test-wa', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
            body: JSON.stringify({
                phone_number: form.phone_number,
            }),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            toast.success(data.message || 'Uji coba WhatsApp berhasil! 💬');
        } else {
            toast.warning(data.message || 'Gagal mengirim pesan WhatsApp uji coba.');
        }
    } catch (e: any) {
        toast.error(e.message || 'Koneksi gagal.');
    } finally {
        isTestingWA.value = false;
    }
}

// Remove registered device
async function removeSubscription(id: number) {
    if (!confirm('Apakah kamu yakin ingin menghapus perangkat ini dari daftar notifikasi?')) return;

    try {
        const res = await fetch('/settings/notifications/unsubscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
            body: JSON.stringify({ id }),
        });
        if (res.ok) {
            toast.info('Perangkat berhasil dihapus dari langganan notifikasi.');
            router.reload({ only: ['subscriptions'] });
            checkPushStatus();
        }
    } catch (e) {
        console.error(e);
    }
}

function submitPreferences() {
    form.put('/settings/notifications/preferences', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Pengaturan notifikasi berhasil diperbarui.');
        },
    });
}

function getCookie(name: string): string {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return decodeURIComponent(parts.pop()!.split(';').shift()!);
    return '';
}
</script>

<template>
    <Head title="Pengaturan Notifikasi - StudyHub" />

    <div class="space-y-8">
        <div>
            <Heading
                variant="small"
                title="Pengaturan Notifikasi & Pengingat"
                description="Kelola pemberitahuan di layar HP serta pesan pengingat otomatis via WhatsApp untuk batas waktu tugasmu."
            />
        </div>

        <!-- 1. KARTU NOTIFIKASI LAYAR HP (WEB PUSH PWA) -->
        <div class="rounded-xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/60">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                        <Smartphone class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                            Notifikasi Layar HP & Browser (Web Push)
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Muncul langsung di status bar & layar kunci (*lock screen*) HP saat ada deadline tugas.
                        </p>
                    </div>
                </div>

                <!-- Status Badge -->
                <div class="shrink-0">
                    <span
                        v-if="permissionState === 'granted' && currentDeviceSubscribed"
                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                    >
                        <CheckCircle2 class="size-3.5" />
                        Aktif di Perangkat Ini
                    </span>
                    <span
                        v-else-if="permissionState === 'denied'"
                        class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/10 px-2.5 py-1 text-xs font-medium text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                    >
                        <XCircle class="size-3.5" />
                        Izin Ditolak Browser
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
                    >
                        <AlertCircle class="size-3.5" />
                        Belum Diaktifkan
                    </span>
                </div>
            </div>

            <!-- Action Buttons for Push -->
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    :disabled="isSubscribing"
                    @click="subscribeToPush"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs font-medium"
                >
                    <Bell class="mr-2 size-4" />
                    {{ currentDeviceSubscribed ? 'Perbarui Izin di Perangkat Ini' : 'Aktifkan Notifikasi di Perangkat Ini' }}
                </Button>

                <Button
                    v-if="subscriptions.length > 0"
                    type="button"
                    variant="outline"
                    :disabled="isTestingPush"
                    @click="sendTestPush"
                    class="border-slate-300 dark:border-slate-700"
                >
                    <Send class="mr-2 size-4" />
                    {{ isTestingPush ? 'Mengirim...' : 'Kirim Notifikasi Uji Coba' }}
                </Button>
            </div>

            <!-- Platform Guide (Android & iOS) -->
            <div class="mt-5 rounded-lg border border-slate-200/60 bg-slate-50/70 p-3.5 text-xs text-slate-600 dark:border-slate-800/80 dark:bg-slate-950/40 dark:text-slate-400 space-y-1.5">
                <div class="flex items-center gap-2 font-medium text-slate-800 dark:text-slate-200">
                    <Info class="size-4 text-indigo-500" />
                    <span>Petunjuk Notifikasi di HP:</span>
                </div>
                <p>
                    • <strong>Android:</strong> Cukup tekan tombol "Aktifkan" di atas dan pilih "Izinkan" saat browser Chrome meminta izin.
                </p>
                <p>
                    • <strong>iPhone / iOS (Safari):</strong> Tap tombol <em>Share</em> (kotak panah ke atas) lalu pilih <strong>"Add to Home Screen"</strong> (*Tambahkan ke Layar Utama*). Buka StudyHub dari ikon tersebut untuk menerima notifikasi di Lock Screen & Dynamic Island.
                </p>
            </div>

            <!-- Registered Devices List -->
            <div class="mt-6 border-t border-slate-200/70 pt-5 dark:border-slate-800">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Perangkat Terdaftar ({{ subscriptions.length }})
                </h3>

                <div v-if="subscriptions.length === 0" class="mt-3 text-xs text-slate-500 italic">
                    Belum ada perangkat yang didaftarkan. Klik tombol di atas untuk mendaftarkan perangkat ini.
                </div>

                <div v-else class="mt-3 space-y-2">
                    <div
                        v-for="sub in subscriptions"
                        :key="sub.id"
                        class="flex items-center justify-between rounded-lg border border-slate-200/60 bg-white/40 px-3.5 py-2.5 text-xs dark:border-slate-800 dark:bg-slate-900/40"
                    >
                        <div class="flex items-center gap-2.5">
                            <Laptop v-if="sub.device_name?.includes('PC') || sub.device_name?.includes('Mac')" class="size-4 text-slate-500" />
                            <Smartphone v-else class="size-4 text-slate-500" />
                            <div>
                                <div class="font-medium text-slate-800 dark:text-slate-200">{{ sub.device_name }}</div>
                                <div class="text-[11px] text-slate-400">Terdaftar sejak {{ new Date(sub.created_at).toLocaleDateString('id-ID') }}</div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="removeSubscription(sub.id)"
                            class="text-slate-400 hover:text-rose-500 transition-colors p-1"
                            title="Hapus perangkat ini"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. KARTU PENGINGAT WHATSAPP -->
        <div class="rounded-xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/60">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                        <MessageCircle class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                            Pengingat Otomatis WhatsApp
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Terima pesan chat otomatis di nomor WhatsApp pribadi saat ada tugas yang harus segera diselesaikan.
                        </p>
                    </div>
                </div>

                <!-- Provider Badge -->
                <div class="shrink-0">
                    <span
                        v-if="waProvider === 'log'"
                        class="inline-flex items-center gap-1.5 rounded-full bg-blue-500/10 px-2.5 py-1 text-xs font-medium text-blue-600 dark:bg-blue-500/20 dark:text-blue-400"
                        title="Mode simulasi aktif untuk pengetesan lokal tanpa biaya"
                    >
                        <Info class="size-3.5" />
                        Mode Simulasi (Dev)
                    </span>
                    <span
                        v-else-if="waConfigured"
                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                    >
                        <CheckCircle2 class="size-3.5" />
                        Gateway Terhubung
                    </span>
                </div>
            </div>

            <!-- WhatsApp Configuration Form -->
            <form @submit.prevent="submitPreferences" class="mt-6 space-y-5">
                <div class="grid gap-2">
                    <Label for="phone_number" class="text-xs font-medium">Nomor WhatsApp Pengguna</Label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <Phone class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" />
                            <Input
                                id="phone_number"
                                v-model="form.phone_number"
                                type="text"
                                class="pl-9 text-sm"
                                placeholder="Contoh: 08123456789 atau 628123456789"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="isTestingWA || !form.phone_number"
                            @click="sendTestWA"
                            class="border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 shrink-0"
                        >
                            <Send class="mr-1.5 size-3.5" />
                            {{ isTestingWA ? 'Mengirim...' : 'Kirim Tes WA' }}
                        </Button>
                    </div>
                    <InputError :message="form.errors.phone_number" />
                    <p class="text-[11px] text-slate-500">
                        Format nomor bisa menggunakan <code>08...</code> atau <code>628...</code>.
                    </p>
                </div>

                <!-- Enable WA Toggle -->
                <div class="flex items-center justify-between rounded-lg border border-slate-200/60 bg-white/40 p-3.5 dark:border-slate-800 dark:bg-slate-900/40">
                    <div>
                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                            Kirim Pengingat Tugas via WhatsApp
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Aktifkan pengiriman pesan otomatis ke nomor di atas sebelum batas waktu tugas habis.
                        </div>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input
                            type="checkbox"
                            v-model="form.wa_notifications_enabled"
                            class="peer sr-only"
                        />
                        <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden dark:bg-slate-700"></div>
                    </label>
                </div>

                <!-- Submit WA Preferences Button -->
                <div class="flex justify-end">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan WhatsApp' }}
                    </Button>
                </div>
            </form>
        </div>

        <!-- 3. KARTU PREFERENSI WAKTU PENGINGAT -->
        <div class="rounded-xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/60">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                    <Clock class="size-5" />
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                        Preferensi Waktu Pengingat Deadline
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Pilih kapan saja StudyHub harus mengingatkan kamu tentang tugas yang sedang berjalan.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitPreferences" class="mt-6 space-y-4">
                <!-- Checkbox H-1 -->
                <label class="flex items-start gap-3 rounded-lg border border-slate-200/60 bg-white/40 p-3.5 cursor-pointer hover:bg-slate-50/50 dark:border-slate-800 dark:bg-slate-900/40 dark:hover:bg-slate-850/50 transition-colors">
                    <input
                        type="checkbox"
                        v-model="form.notify_h_minus_1"
                        class="mt-0.5 size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700"
                    />
                    <div>
                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                            Pengingat H-1 (1 Hari Sebelum Deadline)
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Mengingatkan kamu agar bisa mencicil atau mempersiapkan tugas sebelum hari pengumpulan.
                        </div>
                    </div>
                </label>

                <!-- Checkbox Hari-H -->
                <label class="flex items-start gap-3 rounded-lg border border-slate-200/60 bg-white/40 p-3.5 cursor-pointer hover:bg-slate-50/50 dark:border-slate-800 dark:bg-slate-900/40 dark:hover:bg-slate-850/50 transition-colors">
                    <input
                        type="checkbox"
                        v-model="form.notify_due_today"
                        class="mt-0.5 size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700"
                    />
                    <div>
                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                            Peringatan Hari-H (Batas Waktu Hari Ini)
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Memberitahukan tugas penting yang harus diselesaikan dan dikumpulkan hari ini.
                        </div>
                    </div>
                </label>

                <!-- Checkbox Overdue -->
                <label class="flex items-start gap-3 rounded-lg border border-slate-200/60 bg-white/40 p-3.5 cursor-pointer hover:bg-slate-50/50 dark:border-slate-800 dark:bg-slate-900/40 dark:hover:bg-slate-850/50 transition-colors">
                    <input
                        type="checkbox"
                        v-model="form.notify_overdue"
                        class="mt-0.5 size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700"
                    />
                    <div>
                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                            Peringatan Tugas Terlewat (*Overdue*)
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Mengirim peringatan segera jika ada tugas belum selesai yang telah melewati batas waktu pengumpulan.
                        </div>
                    </div>
                </label>

                <div class="flex justify-end pt-2">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Semua Preferensi' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
