<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Task;
use App\Services\NotificationService;
use App\Services\SchoolSessionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(
        protected SchoolSessionService $sessionService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Auto-prune tasks completed more than 7 days ago (1 week)
     */
    protected function pruneExpiredCompletedTasks(int $userId): void
    {
        $oneWeekAgo = Carbon::now()->subDays(7);
        Task::where('user_id', $userId)
            ->where('status', 'completed')
            ->where(function ($q) use ($oneWeekAgo) {
                $q->where('completed_at', '<', $oneWeekAgo)
                  ->orWhere(function ($sub) use ($oneWeekAgo) {
                      $sub->whereNull('completed_at')
                          ->where('updated_at', '<', $oneWeekAgo);
                  });
            })
            ->delete();
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $this->sessionService->syncUserSession($user);
        $this->pruneExpiredCompletedTasks($user->id);

        $activeSession = $user->currentViewSession();
        $sessionFilter = $request->input('session', $activeSession);

        $tab = $request->input('tab', 'all');

        // If user explicitly asks for completed tab, redirect to dedicated history page
        if ($tab === 'completed') {
            return redirect()->route('tasks.history', [
                'session' => $sessionFilter !== 'all' ? $sessionFilter : undefined,
            ]);
        }

        // Active tasks only (exclude completed tasks from main list)
        $query = Task::with('subject')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'completed');

        if ($sessionFilter !== 'all') {
            $query->where('session', $sessionFilter);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Subject filter
        if ($subjectId = $request->input('subject_id')) {
            if ($subjectId !== 'all') {
                $query->where('subject_id', $subjectId);
            }
        }

        // Priority filter
        if ($priority = $request->input('priority')) {
            if ($priority !== 'all') {
                $query->where('priority', $priority);
            }
        }

        $now = Carbon::now();

        if ($tab === 'pending') {
            $query->where('status', 'pending');
        } elseif ($tab === 'in_progress') {
            $query->where('status', 'in_progress');
        } elseif ($tab === 'overdue') {
            $query->where('deadline', '<', $now);
        }

        // Sort order
        $sortOrder = $request->input('sort', 'deadline_asc');
        if ($sortOrder === 'deadline_desc') {
            $query->orderBy('deadline', 'desc');
        } else {
            $query->orderBy('deadline', 'asc');
        }

        $tasks = $query->get();

        // Subjects for dropdown (filtered by session if not 'all')
        $subjectsQuery = Subject::where('user_id', $user->id)->orderBy('name');
        if ($sessionFilter !== 'all') {
            $subjectsQuery->where('session', $sessionFilter);
        }
        $subjects = $subjectsQuery->get();

        // Base count query for active tasks with sessionFilter
        $baseCount = Task::where('user_id', $user->id);
        if ($sessionFilter !== 'all') {
            $baseCount->where('session', $sessionFilter);
        }

        // Counts for tabs (active tasks only)
        $allActiveCount = (clone $baseCount)->where('status', '!=', 'completed')->count();
        $pendingCount = (clone $baseCount)->where('status', 'pending')->count();
        $inProgressCount = (clone $baseCount)->where('status', 'in_progress')->count();
        $completedCount = (clone $baseCount)->where('status', 'completed')->count();
        $overdueCount = (clone $baseCount)
            ->where('status', '!=', 'completed')
            ->where('deadline', '<', $now)
            ->count();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'subjects' => $subjects,
            'active_session' => $activeSession,
            'filters' => [
                'tab' => $tab,
                'search' => $search ?? '',
                'subject_id' => $subjectId ?? 'all',
                'priority' => $priority ?? 'all',
                'sort' => $sortOrder,
                'session' => $sessionFilter,
            ],
            'counts' => [
                'all' => $allActiveCount,
                'pending' => $pendingCount,
                'in_progress' => $inProgressCount,
                'completed' => $completedCount,
                'overdue' => $overdueCount,
            ],
        ]);
    }

    /**
     * Display completed tasks history (kept for 7 days before reset)
     */
    public function history(Request $request): Response
    {
        $user = $request->user();
        $this->sessionService->syncUserSession($user);
        $this->pruneExpiredCompletedTasks($user->id);

        $activeSession = $user->currentViewSession();
        $sessionFilter = $request->input('session', $activeSession);

        $query = Task::with('subject')
            ->where('user_id', $user->id)
            ->where('status', 'completed');

        if ($sessionFilter !== 'all') {
            $query->where('session', $sessionFilter);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($subjectId = $request->input('subject_id')) {
            if ($subjectId !== 'all') {
                $query->where('subject_id', $subjectId);
            }
        }

        $sortOrder = $request->input('sort', 'completed_desc');
        if ($sortOrder === 'completed_asc') {
            $query->orderBy('completed_at', 'asc');
        } elseif ($sortOrder === 'deadline_asc') {
            $query->orderBy('deadline', 'asc');
        } else {
            $query->orderBy('completed_at', 'desc');
        }

        $tasks = $query->get();

        $subjectsQuery = Subject::where('user_id', $user->id)->orderBy('name');
        if ($sessionFilter !== 'all') {
            $subjectsQuery->where('session', $sessionFilter);
        }
        $subjects = $subjectsQuery->get();

        $totalCompleted = Task::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        return Inertia::render('Tasks/History', [
            'tasks' => $tasks,
            'subjects' => $subjects,
            'active_session' => $activeSession,
            'filters' => [
                'search' => $search ?? '',
                'subject_id' => $subjectId ?? 'all',
                'sort' => $sortOrder,
                'session' => $sessionFilter,
            ],
            'total_completed' => $totalCompleted,
        ]);
    }

    public function resetHistory(Request $request): RedirectResponse
    {
        $user = $request->user();

        Task::where('user_id', $user->id)
            ->where('status', 'completed')
            ->delete();

        return back()->with('success', 'Histori tugas selesai berhasil di-reset.');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'subject_id' => 'nullable|exists:subjects,id',
            'new_subject_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'deadline' => 'required|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
            'session' => 'nullable|in:teori,bengkel',
        ]);

        $activeSession = $user->currentViewSession();
        $session = $validated['session'] ?? null;

        $subjectId = $validated['subject_id'] ?? null;
        if (!empty($validated['new_subject_name'])) {
            $newSubject = Subject::firstOrCreate([
                'user_id' => $user->id,
                'name' => trim($validated['new_subject_name']),
            ], [
                'session' => $session ?: $activeSession,
            ]);
            $subjectId = $newSubject->id;
        }

        if (!$session) {
            if ($subjectId) {
                $sub = Subject::find($subjectId);
                $session = $sub?->session ?: $activeSession;
            } else {
                $session = $activeSession;
            }
        }

        $completedAt = $validated['status'] === 'completed' ? Carbon::now() : null;

        $task = Task::create([
            'user_id' => $user->id,
            'subject_id' => $subjectId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'deadline' => Carbon::parse($validated['deadline']),
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'completed_at' => $completedAt,
            'session' => $session,
        ]);

        // Send instant WhatsApp notification if user has enabled it
        if ($user->wa_notifications_enabled && ! empty($user->phone_number)) {
            app(\App\Services\WhatsAppService::class)->sendNewTaskNotification($user, $task);
        }

        return back()->with('success', 'Tugas berhasil ditambahkan.');
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $user = $request->user();
        if ($task->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'subject_id' => 'nullable|exists:subjects,id',
            'new_subject_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'deadline' => 'required|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
            'session' => 'nullable|in:teori,bengkel',
        ]);

        $subjectId = $validated['subject_id'] ?? null;
        $session = $validated['session'] ?? $task->session;

        if (!empty($validated['new_subject_name'])) {
            $newSubject = Subject::firstOrCreate([
                'user_id' => $user->id,
                'name' => trim($validated['new_subject_name']),
            ], [
                'session' => $session,
            ]);
            $subjectId = $newSubject->id;
        }

        $completedAt = $task->completed_at;
        if ($validated['status'] === 'completed' && !$completedAt) {
            $completedAt = Carbon::now();
        } elseif ($validated['status'] !== 'completed') {
            $completedAt = null;
        }

        $task->update([
            'subject_id' => $subjectId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'deadline' => Carbon::parse($validated['deadline']),
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'completed_at' => $completedAt,
            'session' => $session,
        ]);

        if ($validated['status'] === 'completed') {
            $this->notificationService->cleanForTask($user->id, $task->id);
        }

        return back()->with('success', 'Tugas berhasil diperbarui.');
    }

    public function toggle(Request $request, Task $task): RedirectResponse
    {
        $user = $request->user();
        if ($task->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        if ($task->status === 'completed') {
            $task->update([
                'status' => 'pending',
                'completed_at' => null,
            ]);
            $msg = 'Tugas dikembalikan ke daftar tugas aktif.';
        } else {
            $task->update([
                'status' => 'completed',
                'completed_at' => Carbon::now(),
            ]);
            $this->notificationService->cleanForTask($user->id, $task->id);
            $msg = 'Tugas diselesaikan dan dipindahkan ke histori selesai! 🎉';
        }

        return back()->with('success', $msg);
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $user = $request->user();
        if ($task->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $newStatus = $validated['status'];
        $completedAt = $task->completed_at;

        if ($newStatus === 'completed' && !$completedAt) {
            $completedAt = Carbon::now();
        } elseif ($newStatus !== 'completed') {
            $completedAt = null;
        }

        $task->update([
            'status' => $newStatus,
            'completed_at' => $completedAt,
        ]);

        if ($newStatus === 'completed') {
            $this->notificationService->cleanForTask($user->id, $task->id);
        }

        $msg = match ($newStatus) {
            'completed' => 'Tugas selesai dan dipindahkan ke histori selesai! 🎉',
            'in_progress' => 'Status tugas diubah menjadi sedang dikerjakan. Semangat! 💪',
            default => 'Status tugas diubah menjadi belum dikerjakan.',
        };

        return back()->with('success', $msg);
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $user = $request->user();
        if ($task->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $this->notificationService->cleanForTask($user->id, $task->id);
        $task->delete();

        return back()->with('success', 'Tugas berhasil dihapus.');
    }
}
