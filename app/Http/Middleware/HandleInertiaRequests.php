<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $notifications = [];
        $unreadCount = 0;
        $schoolSession = null;

        if ($user) {
            $sessionService = app(\App\Services\SchoolSessionService::class);
            $user = $sessionService->syncUserSession($user);
            $schoolSession = $sessionService->getSessionInfo($user);

            app(\App\Services\NotificationService::class)->syncForUser($user);
            $notifications = \App\Models\UserNotification::where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->take(15)
                ->get();
            $unreadCount = \App\Models\UserNotification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'school_session' => $schoolSession,
            'notifications' => $notifications,
            'unread_notifications_count' => $unreadCount,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
