<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Remove all notifications related to a specific task.
     */
    public function cleanForTask(int $userId, int $taskId): void
    {
        UserNotification::where('user_id', $userId)
            ->where(function ($q) use ($taskId) {
                $q->where('key', 'like', "task_overdue_{$taskId}_%")
                  ->orWhere('key', 'like', "task_due_today_{$taskId}_%")
                  ->orWhere('key', 'like', "task_due_tomorrow_{$taskId}_%");
            })
            ->delete();
    }

    /**
     * Synchronize and generate fresh contextual notifications for the user.
     */
    public function syncForUser(User $user): void
    {
        $now = Carbon::now();
        $todayStr = $now->toDateString();
        $tomorrowStr = $now->copy()->addDay()->toDateString();
        $currentDay = strtolower($now->format('l'));

        // Clean up notifications for tasks that are completed or no longer exist
        $existingTaskNotifications = UserNotification::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('key', 'like', 'task_overdue_%')
                  ->orWhere('key', 'like', 'task_due_today_%')
                  ->orWhere('key', 'like', 'task_due_tomorrow_%');
            })
            ->get();

        if ($existingTaskNotifications->isNotEmpty()) {
            $activeTaskIds = Task::where('user_id', $user->id)
                ->where('status', '!=', 'completed')
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->flip();

            $notifIdsToDelete = [];
            foreach ($existingTaskNotifications as $notif) {
                if (preg_match('/^task_(?:overdue|due_today|due_tomorrow)_(\d+)_/', $notif->key, $matches)) {
                    $taskId = $matches[1];
                    if (!isset($activeTaskIds[$taskId])) {
                        $notifIdsToDelete[] = $notif->id;
                    }
                }
            }

            if (!empty($notifIdsToDelete)) {
                UserNotification::whereIn('id', $notifIdsToDelete)->delete();
            }
        }

        // 1. Overdue Tasks (deadline in the past and not completed)
        $overdueTasks = Task::with('subject')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'completed')
            ->where('deadline', '<', $now)
            ->get();

        foreach ($overdueTasks as $task) {
            $key = "task_overdue_{$task->id}_{$todayStr}";
            $subjectName = $task->subject ? $task->subject->name : 'Mata Pelajaran';
            $deadlineTime = $task->formatted_deadline ?: $task->deadline->format('d/m/Y H:i');

            UserNotification::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'key' => $key,
                ],
                [
                    'title' => "Tugas Terlambat: {$task->title}",
                    'message' => "Tugas {$subjectName} telah melewati tenggat waktu pada {$deadlineTime}. Segera selesaikan!",
                    'type' => 'danger',
                    'action_url' => '/tasks?tab=overdue',
                ]
            );
        }

        // 2. Tasks Due Today (deadline is today and in the future)
        $dueTodayTasks = Task::with('subject')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'completed')
            ->whereDate('deadline', $todayStr)
            ->where('deadline', '>=', $now)
            ->get();

        foreach ($dueTodayTasks as $task) {
            $key = "task_due_today_{$task->id}_{$todayStr}";
            $subjectName = $task->subject ? $task->subject->name : 'Mata Pelajaran';
            $deadlineTime = $task->deadline->format('H:i');

            UserNotification::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'key' => $key,
                ],
                [
                    'title' => "Deadline Hari Ini: {$task->title}",
                    'message' => "Tugas {$subjectName} batas pengumpulan hari ini pukul {$deadlineTime}. Jangan sampai terlewat!",
                    'type' => 'warning',
                    'action_url' => '/tasks',
                ]
            );
        }

        // 3. Tasks Due Tomorrow
        $dueTomorrowTasks = Task::with('subject')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'completed')
            ->whereDate('deadline', $tomorrowStr)
            ->get();

        foreach ($dueTomorrowTasks as $task) {
            $key = "task_due_tomorrow_{$task->id}_{$tomorrowStr}";
            $subjectName = $task->subject ? $task->subject->name : 'Mata Pelajaran';
            $deadlineTime = $task->deadline->format('H:i');

            UserNotification::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'key' => $key,
                ],
                [
                    'title' => "Deadline Besok: {$task->title}",
                    'message' => "Tugas {$subjectName} deadline besok pukul {$deadlineTime}. Cicil pengerjaannya sekarang.",
                    'type' => 'warning',
                    'action_url' => '/tasks',
                ]
            );
        }

        // 4. Today's Class Schedules
        $todaySchedules = Schedule::with('subject')
            ->where('user_id', $user->id)
            ->where('day', $currentDay)
            ->get();

        if ($todaySchedules->isNotEmpty()) {
            $key = "schedule_today_{$user->id}_{$todayStr}";
            $subjectNames = $todaySchedules->map(fn($s) => $s->subject?->name ?: 'Pelajaran')->take(3)->implode(', ');
            $totalCount = $todaySchedules->count();
            $moreText = $totalCount > 3 ? " dan " . ($totalCount - 3) . " mapel lainnya" : "";

            UserNotification::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'key' => $key,
                ],
                [
                    'title' => 'Jadwal Pelajaran Hari Ini',
                    'message' => "Kamu memiliki {$totalCount} kelas hari ini: {$subjectNames}{$moreText}. Cek ruangan & jam pelajaranmu.",
                    'type' => 'info',
                    'action_url' => '/schedule',
                ]
            );
        }

        // 5. Active To-Do reminder
        $pendingTodosCount = Todo::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingTodosCount > 0) {
            $key = "todo_reminder_{$user->id}_{$todayStr}";
            UserNotification::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'key' => $key,
                ],
                [
                    'title' => 'Target To-Do Harian',
                    'message' => "Ada {$pendingTodosCount} aktivitas to-do yang menunggu untuk diselesaikan hari ini. Semangat!",
                    'type' => 'info',
                    'action_url' => '/todos',
                ]
            );
        } else {
            UserNotification::where('user_id', $user->id)
                ->where('key', 'like', "todo_reminder_{$user->id}_%")
                ->delete();
        }
    }
}
