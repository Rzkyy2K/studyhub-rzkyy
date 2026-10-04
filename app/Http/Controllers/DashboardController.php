<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Task;
use App\Models\Todo;
use App\Services\SchoolSessionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected SchoolSessionService $sessionService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->sessionService->syncUserSession($user);
        $activeSession = $user->currentViewSession();

        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $currentDay = strtolower($now->format('l')); // monday, tuesday, etc.

        // Summary counts for active session
        $uncompletedTasksCount = Task::where('user_id', $user->id)
            ->where('session', $activeSession)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $dueTodayCount = Task::where('user_id', $user->id)
            ->where('session', $activeSession)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereBetween('deadline', [$todayStart, $todayEnd])
            ->count();

        $completedTasksCount = Task::where('user_id', $user->id)
            ->where('session', $activeSession)
            ->where('status', 'completed')
            ->count();

        $pendingTodosCount = Todo::where('user_id', $user->id)
            ->whereIn('session', [$activeSession, 'all'])
            ->where('status', 'pending')
            ->count();

        // Fokus Hari Ini: upcoming uncompleted tasks ordered by nearest deadline for active session
        $focusTasks = Task::with('subject')
            ->where('user_id', $user->id)
            ->where('session', $activeSession)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('deadline', 'asc')
            ->take(5)
            ->get();

        // Jadwal Hari Ini for active session
        $todaySchedules = Schedule::with('subject')
            ->where('user_id', $user->id)
            ->where('session', $activeSession)
            ->where('day', $currentDay)
            ->orderBy('start_time', 'asc')
            ->get();

        // Subjects for active session (used in quick action modal)
        $subjects = Subject::where('user_id', $user->id)
            ->where('session', $activeSession)
            ->orderBy('name')
            ->get();

        // Indonesian date format
        $formattedDate = $now->translatedFormat('l, j F Y');
        $sessionInfo = $this->sessionService->getSessionInfo($user);

        // Pending todos list for quick dashboard widget
        $pendingTodosList = Todo::where('user_id', $user->id)
            ->whereIn('session', [$activeSession, 'all'])
            ->where('status', 'pending')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'uncompleted_tasks' => $uncompletedTasksCount,
                'due_today_tasks' => $dueTodayCount,
                'completed_tasks' => $completedTasksCount,
                'pending_todos' => $pendingTodosCount,
            ],
            'focus_tasks' => $focusTasks,
            'today_schedules' => $todaySchedules,
            'pending_todos_list' => $pendingTodosList,
            'subjects' => $subjects,
            'current_day' => $currentDay,
            'formatted_date' => $formattedDate,
            'active_session' => $activeSession,
            'session_info' => $sessionInfo,
        ]);
    }
}
