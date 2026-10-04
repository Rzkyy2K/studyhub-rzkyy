<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class SchoolSessionService
{
    public const DEFAULT_ANCHOR_START = '2026-10-05';
    public const DEFAULT_ANCHOR_END = '2026-11-05';

    /**
     * Synchronize and auto-rotate the user's official session based on current date.
     */
    public function syncUserSession(User $user, ?Carbon $now = null): User
    {
        $now = $now ?? Carbon::now();

        // 1. Initial setup if not already set
        if (! $user->session_started_at || ! $user->session_ends_at) {
            $user->active_session = $user->active_session ?: 'teori';
            $user->session_started_at = Carbon::parse(self::DEFAULT_ANCHOR_START);
            $user->session_ends_at = Carbon::parse(self::DEFAULT_ANCHOR_END);
            $user->view_session = $user->active_session;
            $user->save();
        }

        // 2. Auto-rotate if current date is at or past session_ends_at
        $needsSave = false;
        $endsAt = Carbon::parse($user->session_ends_at);

        while ($now->greaterThanOrEqualTo($endsAt)) {
            $user->session_started_at = $endsAt->copy();
            $endsAt = $endsAt->copy()->addMonth();
            $user->session_ends_at = $endsAt;
            $user->active_session = ($user->active_session === 'teori') ? 'bengkel' : 'teori';
            $user->view_session = $user->active_session;
            $needsSave = true;
        }

        if ($needsSave) {
            $user->save();
        }

        return $user;
    }

    /**
     * Switch viewed session without resetting the official session validity period.
     */
    public function switchViewSession(User $user, string $targetSession): User
    {
        if (! in_array($targetSession, ['teori', 'bengkel'])) {
            $targetSession = 'teori';
        }

        $user->view_session = $targetSession;
        $user->save();

        return $user;
    }

    /**
     * Alias for switchViewSession to ensure backward compatibility and prevent period resets.
     */
    public function switchSession(User $user, string $targetSession, ?Carbon $now = null): User
    {
        return $this->switchViewSession($user, $targetSession);
    }

    /**
     * Officially start a new 1-month cycle from today (only when explicitly requested).
     */
    public function startNewCycle(User $user, string $targetSession, ?Carbon $now = null): User
    {
        $now = $now ?? Carbon::now();

        if (! in_array($targetSession, ['teori', 'bengkel'])) {
            $targetSession = 'teori';
        }

        $user->active_session = $targetSession;
        $user->view_session = $targetSession;
        $user->session_started_at = $now->copy()->startOfDay();
        $user->session_ends_at = $now->copy()->startOfDay()->addMonth();
        $user->save();

        return $user;
    }

    /**
     * Extend current session by a specified number of days (e.g. 7 days / 1 week).
     */
    public function extendSession(User $user, int $days = 7): User
    {
        $endsAt = Carbon::parse($user->session_ends_at ?? self::DEFAULT_ANCHOR_END);
        $user->session_ends_at = $endsAt->copy()->addDays($days);
        $user->save();

        return $user;
    }

    /**
     * Get detailed information about the current session for UI presentation.
     */
    public function getSessionInfo(User $user, ?Carbon $now = null): array
    {
        $now = $now ?? Carbon::now();
        $user = $this->syncUserSession($user, $now);

        $officialSession = $user->active_session ?: 'teori';
        $viewSession = $user->currentViewSession();
        $isViewingOfficial = ($viewSession === $officialSession);

        $startedAt = Carbon::parse($user->session_started_at);
        $endsAt = Carbon::parse($user->session_ends_at);

        $isTeoriView = $viewSession === 'teori';
        $isTeoriOfficial = $officialSession === 'teori';
        $daysRemaining = max(0, $now->diffInDays($endsAt, false));

        return [
            'active_session' => $viewSession,
            'official_session' => $officialSession,
            'view_session' => $viewSession,
            'is_viewing_official' => $isViewingOfficial,
            'label' => $isTeoriView ? 'Sesi Teori' : 'Sesi Bengkel',
            'type_name' => $isTeoriView ? 'Teori' : 'Bengkel',
            'subtitle' => $isTeoriView ? 'Pembelajaran umum & akademik' : 'Praktik kejuruan & workshop',
            'official_label' => $isTeoriOfficial ? 'Sesi Teori' : 'Sesi Bengkel',
            'period_start' => $startedAt->locale('id')->isoFormat('D MMMM YYYY'),
            'period_end' => $endsAt->locale('id')->isoFormat('D MMMM YYYY'),
            'period_range' => $startedAt->locale('id')->isoFormat('D MMM YYYY') . ' – ' . $endsAt->locale('id')->isoFormat('D MMM YYYY'),
            'days_remaining' => (int) ceil($daysRemaining),
            'started_at_raw' => $startedAt->toDateString(),
            'ends_at_raw' => $endsAt->toDateString(),
        ];
    }
}
