<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Subject;
use App\Services\SchoolSessionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
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

        $query = Schedule::with('subject')
            ->where('user_id', $user->id)
            ->orderBy('start_time', 'asc');

        if ($sessionFilter !== 'all') {
            $query->where('session', $sessionFilter);
        }

        $schedules = $query->get();

        $subjectsQuery = Subject::where('user_id', $user->id)->orderBy('name');
        if ($sessionFilter !== 'all') {
            $subjectsQuery->where('session', $sessionFilter);
        }
        $subjects = $subjectsQuery->get();

        $currentDay = strtolower(Carbon::now()->format('l'));

        return Inertia::render('Schedule/Index', [
            'schedules' => $schedules,
            'subjects' => $subjects,
            'current_day' => $currentDay,
            'active_session' => $activeSession,
            'session_filter' => $sessionFilter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday',
            'subject_id' => 'nullable|exists:subjects,id',
            'new_subject_name' => 'nullable|string|max:100',
            'teacher' => 'nullable|string|max:100',
            'room' => 'nullable|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'session' => 'nullable|in:teori,bengkel',
        ], [
            'end_time.after' => 'Jam selesai tidak boleh lebih awal dari jam mulai.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'day.required' => 'Hari wajib dipilih.',
        ]);

        $activeSession = $user->currentViewSession();
        $session = $validated['session'] ?? null;

        $subjectId = $validated['subject_id'] ?? null;
        if (!empty($validated['new_subject_name'])) {
            $newSubject = Subject::firstOrCreate([
                'user_id' => $user->id,
                'name' => trim($validated['new_subject_name']),
            ], [
                'teacher' => $validated['teacher'] ?? null,
                'room' => $validated['room'] ?? null,
                'session' => $session ?: $activeSession,
            ]);
            $subjectId = $newSubject->id;
        } elseif ($subjectId && (!empty($validated['teacher']) || !empty($validated['room']))) {
            $subj = Subject::where('user_id', $user->id)->find($subjectId);
            if ($subj) {
                if (!empty($validated['teacher']) && empty($subj->teacher)) {
                    $subj->update(['teacher' => $validated['teacher']]);
                }
                if (!empty($validated['room']) && empty($subj->room)) {
                    $subj->update(['room' => $validated['room']]);
                }
            }
        }

        if (!$session) {
            if ($subjectId) {
                $sub = Subject::find($subjectId);
                $session = $sub?->session ?: $activeSession;
            } else {
                $session = $activeSession;
            }
        }

        Schedule::create([
            'user_id' => $user->id,
            'subject_id' => $subjectId,
            'day' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room' => $validated['room'] ?? null,
            'session' => $session,
        ]);

        return back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $user = $request->user();
        if ($schedule->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday',
            'subject_id' => 'nullable|exists:subjects,id',
            'new_subject_name' => 'nullable|string|max:100',
            'teacher' => 'nullable|string|max:100',
            'room' => 'nullable|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'session' => 'nullable|in:teori,bengkel',
        ], [
            'end_time.after' => 'Jam selesai tidak boleh lebih awal dari jam mulai.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'day.required' => 'Hari wajib dipilih.',
        ]);

        $session = $validated['session'] ?? $schedule->session;

        $subjectId = $validated['subject_id'] ?? null;
        if (!empty($validated['new_subject_name'])) {
            $newSubject = Subject::firstOrCreate([
                'user_id' => $user->id,
                'name' => trim($validated['new_subject_name']),
            ], [
                'teacher' => $validated['teacher'] ?? null,
                'room' => $validated['room'] ?? null,
                'session' => $session,
            ]);
            $subjectId = $newSubject->id;
        }

        $schedule->update([
            'subject_id' => $subjectId,
            'day' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room' => $validated['room'] ?? null,
            'session' => $session,
        ]);

        return back()->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        $user = $request->user();
        if ($schedule->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $schedule->delete();

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
