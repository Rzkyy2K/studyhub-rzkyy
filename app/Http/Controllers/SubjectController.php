<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\SchoolSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function __construct(
        protected SchoolSessionService $sessionService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->sessionService->syncUserSession($user);

        $activeSession = $user->currentViewSession();
        $sessionFilter = $request->query('session', $activeSession);

        $query = $user->subjects()
            ->withCount(['tasks', 'schedules'])
            ->orderBy('name', 'asc');

        if ($sessionFilter !== 'all') {
            $query->where('session', $sessionFilter);
        }

        $subjects = $query->get();

        // Counts per session tab
        $teoriCount = $user->subjects()->where('session', 'teori')->count();
        $bengkelCount = $user->subjects()->where('session', 'bengkel')->count();
        $allCount = $user->subjects()->count();

        return Inertia::render('Subjects/Index', [
            'subjects' => $subjects,
            'current_session' => $activeSession,
            'active_tab' => $sessionFilter,
            'counts' => [
                'all' => $allCount,
                'teori' => $teoriCount,
                'bengkel' => $bengkelCount,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'teacher' => 'nullable|string|max:100',
            'room' => 'nullable|string|max:50',
            'session' => 'nullable|in:teori,bengkel',
        ]);

        $session = $validated['session'] ?? $user->currentViewSession();

        Subject::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'teacher' => $validated['teacher'] ?? null,
            'room' => $validated['room'] ?? null,
            'session' => $session,
        ]);

        return back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $user = $request->user();

        abort_if($subject->user_id !== $user->id, 403, 'Akses tidak diizinkan.');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'teacher' => 'nullable|string|max:100',
            'room' => 'nullable|string|max:50',
            'session' => 'nullable|in:teori,bengkel',
        ]);

        $subject->update([
            'name' => $validated['name'],
            'teacher' => $validated['teacher'] ?? null,
            'room' => $validated['room'] ?? null,
            'session' => $validated['session'] ?? $subject->session,
        ]);

        return back()->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        $user = $request->user();

        abort_if($subject->user_id !== $user->id, 403, 'Akses tidak diizinkan.');

        $subject->delete();

        return back()->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
