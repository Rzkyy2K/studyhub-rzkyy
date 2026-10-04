<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->notificationService->syncForUser($user);

        $notifications = UserNotification::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(30)
            ->get();

        $unreadCount = UserNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(Request $request, UserNotification $notification): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($notification->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        UserNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function destroy(Request $request, UserNotification $notification): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($notification->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }

    public function clearAll(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        UserNotification::where('user_id', $user->id)->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Semua notifikasi dibersihkan.');
    }
}
