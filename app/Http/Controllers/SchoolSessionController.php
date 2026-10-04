<?php

namespace App\Http\Controllers;

use App\Services\SchoolSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolSessionController extends Controller
{
    public function __construct(
        protected SchoolSessionService $sessionService
    ) {}

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session' => 'required|in:teori,bengkel',
        ]);

        $user = $request->user();
        $this->sessionService->switchViewSession($user, $validated['session']);

        $label = $validated['session'] === 'teori' ? 'Sesi Teori' : 'Sesi Bengkel';

        return back()->with('success', "Beralih ke tampilan {$label}. Masa berlaku sesi resmi tetap terjaga.");
    }

    public function extend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:60',
        ]);

        $days = $validated['days'] ?? 7;
        $user = $request->user();
        $this->sessionService->extendSession($user, $days);

        return back()->with('success', "Masa aktif sesi resmi berhasil diperpanjang {$days} hari.");
    }

    public function startCycle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session' => 'required|in:teori,bengkel',
        ]);

        $user = $request->user();
        $this->sessionService->startNewCycle($user, $validated['session']);

        $label = $validated['session'] === 'teori' ? 'Sesi Teori' : 'Sesi Bengkel';

        return back()->with('success', "Siklus resmi baru untuk {$label} dimulai hari ini (berlaku 1 bulan).");
    }
}
