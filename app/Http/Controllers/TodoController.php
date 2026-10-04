<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Services\SchoolSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodoController extends Controller
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

        $query = Todo::where('user_id', $user->id);

        if ($sessionFilter !== 'all') {
            $query->whereIn('session', [$sessionFilter, 'all']);
        }

        $todos = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        $totalCount = $todos->count();
        $completedCount = $todos->where('status', 'completed')->count();
        $pendingCount = $totalCount - $completedCount;

        return Inertia::render('Todos/Index', [
            'todos' => $todos,
            'active_session' => $activeSession,
            'session_filter' => $sessionFilter,
            'stats' => [
                'total' => $totalCount,
                'completed' => $completedCount,
                'pending' => $pendingCount,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'session' => 'nullable|in:teori,bengkel,all',
        ]);

        $activeSession = $user->currentViewSession();
        $session = $validated['session'] ?? $activeSession;

        Todo::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'pending',
            'session' => $session,
        ]);

        return back()->with('success', 'To-Do berhasil ditambahkan.');
    }

    public function update(Request $request, Todo $todo): RedirectResponse
    {
        $user = $request->user();
        if ($todo->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:pending,completed',
            'session' => 'nullable|in:teori,bengkel,all',
        ]);

        $todo->update([
            'title' => $validated['title'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'] ?? $todo->status,
            'session' => $validated['session'] ?? $todo->session,
        ]);

        return back()->with('success', 'To-Do berhasil diperbarui.');
    }

    public function toggle(Request $request, Todo $todo): RedirectResponse
    {
        $user = $request->user();
        if ($todo->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $newStatus = $todo->status === 'completed' ? 'pending' : 'completed';
        $todo->update(['status' => $newStatus]);

        return back()->with('success', $newStatus === 'completed' ? 'Aktivitas diselesaikan!' : 'Aktivitas ditandai belum selesai.');
    }

    public function destroy(Request $request, Todo $todo): RedirectResponse
    {
        $user = $request->user();
        if ($todo->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $todo->delete();

        return back()->with('success', 'To-Do berhasil dihapus.');
    }
}
