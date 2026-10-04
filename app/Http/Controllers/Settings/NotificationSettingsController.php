<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    /**
     * Show notification settings page.
     */
    public function edit(Request $request, WebPushService $webPushService): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Notifications', [
            'vapidPublicKey' => $webPushService->getPublicKey(),
            'subscriptions' => $user->pushSubscriptions()->select('id', 'device_name', 'created_at')->latest()->get(),
            'waProvider' => env('WA_GATEWAY_PROVIDER', 'log'),
            'waConfigured' => ! empty(env('WA_API_TOKEN')) || env('WA_GATEWAY_PROVIDER', 'log') === 'log',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
                'wa_notifications_enabled' => (bool) $user->wa_notifications_enabled,
                'push_notifications_enabled' => (bool) $user->push_notifications_enabled,
                'notify_h_minus_1' => (bool) $user->notify_h_minus_1,
                'notify_due_today' => (bool) $user->notify_due_today,
                'notify_overdue' => (bool) $user->notify_overdue,
            ],
        ]);
    }

    /**
     * Update user notification preferences.
     */
    public function updatePreferences(Request $request, WhatsAppService $whatsAppService): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => ['nullable', 'string', 'max:20'],
            'wa_notifications_enabled' => ['boolean'],
            'push_notifications_enabled' => ['boolean'],
            'notify_h_minus_1' => ['boolean'],
            'notify_due_today' => ['boolean'],
            'notify_overdue' => ['boolean'],
        ]);

        if (! empty($validated['phone_number'])) {
            $validated['phone_number'] = $whatsAppService->normalizePhoneNumber($validated['phone_number']);
        }

        $request->user()->update($validated);

        return back()->with('status', 'Pengaturan notifikasi berhasil diperbarui.');
    }

    /**
     * Save new push subscription token from browser.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'deviceName' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        $sub = $user->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'public_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
                'content_encoding' => $request->input('contentEncoding', 'aesgcm'),
                'device_name' => $request->input('deviceName', 'Perangkat Web'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Perangkat berhasil didaftarkan untuk notifikasi push.',
            'subscription' => $sub,
        ]);
    }

    /**
     * Delete a push subscription.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => ['nullable', 'string'],
            'id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();

        if ($request->filled('id')) {
            $user->pushSubscriptions()->where('id', $request->id)->delete();
        } elseif ($request->filled('endpoint')) {
            $user->pushSubscriptions()->where('endpoint', $request->endpoint)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Perangkat berhasil dihapus dari langganan notifikasi push.',
        ]);
    }

    /**
     * Send instant test push notification to user's registered devices.
     */
    public function testPush(Request $request, WebPushService $webPushService): JsonResponse
    {
        $user = $request->user();
        $subscriptions = $user->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada perangkat yang terdaftar. Klik "Aktifkan di Perangkat Ini" terlebih dahulu.',
            ], 422);
        }

        $result = $webPushService->sendToUser(
            $user,
            '🔔 Uji Coba Notifikasi StudyHub',
            'Selamat! Notifikasi layar HP kamu telah terhubung dan siap menerima pengingat deadline tugas.',
            '/tasks'
        );

        if ($result['sent'] > 0) {
            return response()->json([
                'success' => true,
                'message' => "Notifikasi berhasil dikirim ke {$result['sent']} perangkat!",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim notifikasi push. Token mungkin telah kedaluwarsa.',
        ], 500);
    }

    /**
     * Send instant test WhatsApp message to user's phone number.
     */
    public function testWhatsApp(Request $request, WhatsAppService $whatsAppService): JsonResponse
    {
        $user = $request->user();
        $phone = $request->input('phone_number') ?: $user->phone_number;

        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp belum diisi.',
            ], 422);
        }

        $appUrl = config('app.url', 'http://localhost');
        $testMessage = "🎓 *Uji Coba Pengingat WhatsApp StudyHub*\n\n"
            . "Halo *{$user->name}*! 👋\n\n"
            . "Nomor WhatsApp kamu (*{$phone}*) telah berhasil terhubung dengan sistem StudyHub.\n"
            . "Kamu akan menerima pesan pengingat otomatis saat ada tugas yang mendekati batas waktu pengumpulan! ⏳\n\n"
            . "Semoga harimu menyenangkan dan belajarmu makin produktif! ✨\n"
            . "🔗 Buka StudyHub: {$appUrl}/tasks";

        $result = $whatsAppService->send($phone, $testMessage);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
