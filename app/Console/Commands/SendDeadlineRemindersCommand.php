<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Services\WebPushService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendDeadlineRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:remind {--force : Abaikan cache pencegah duplikasi pengiriman hari ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi push dan pesan WhatsApp untuk tugas yang mendekati deadline (H-1 & Hari-H)';

    /**
     * Execute the console command.
     */
    public function handle(WebPushService $webPushService, WhatsAppService $whatsAppService): int
    {
        $this->info('Memulai pengecekan tugas mendekati deadline...');

        $now = Carbon::now();
        $todayStr = $now->toDateString();
        $tomorrowStr = $now->copy()->addDay()->toDateString();
        $force = (bool) $this->option('force');

        $users = User::with(['pushSubscriptions'])
            ->where(function ($q) {
                $q->where('push_notifications_enabled', true)
                  ->orWhere('wa_notifications_enabled', true);
            })
            ->get();

        $totalPushed = 0;
        $totalWA = 0;

        foreach ($users as $user) {
            // 1. Tasks Due Tomorrow (H-1)
            if ($user->notify_h_minus_1) {
                $tasksTomorrow = Task::with('subject')
                    ->where('user_id', $user->id)
                    ->where('status', '!=', 'completed')
                    ->whereDate('deadline', $tomorrowStr)
                    ->get();

                foreach ($tasksTomorrow as $task) {
                    $cacheKey = "task_reminder_tomorrow_{$task->id}_{$todayStr}";
                    if ($force || ! Cache::has($cacheKey)) {
                        $subjectName = $task->subject ? $task->subject->name : 'Mata Pelajaran';
                        $deadlineTime = $task->deadline ? $task->deadline->format('H:i') : '';

                        if ($user->push_notifications_enabled) {
                            $webPushService->sendToUser(
                                $user,
                                "⏳ Deadline Besok: {$task->title}",
                                "Tugas {$subjectName} batas pengumpulan besok pukul {$deadlineTime}. Jangan lupa dicicil!",
                                '/tasks'
                            );
                            $totalPushed++;
                        }

                        if ($user->wa_notifications_enabled && ! empty($user->phone_number)) {
                            $whatsAppService->sendTaskReminder($user, $task, 'due_tomorrow');
                            $totalWA++;
                        }

                        Cache::put($cacheKey, true, now()->endOfDay());
                    }
                }
            }

            // 2. Tasks Due Today (H-0)
            if ($user->notify_due_today) {
                $tasksToday = Task::with('subject')
                    ->where('user_id', $user->id)
                    ->where('status', '!=', 'completed')
                    ->whereDate('deadline', $todayStr)
                    ->where('deadline', '>=', $now)
                    ->get();

                foreach ($tasksToday as $task) {
                    $cacheKey = "task_reminder_today_{$task->id}_{$todayStr}";
                    if ($force || ! Cache::has($cacheKey)) {
                        $subjectName = $task->subject ? $task->subject->name : 'Mata Pelajaran';
                        $deadlineTime = $task->deadline ? $task->deadline->format('H:i') : '';

                        if ($user->push_notifications_enabled) {
                            $webPushService->sendToUser(
                                $user,
                                "⚠️ Deadline Hari Ini: {$task->title}",
                                "Tugas {$subjectName} harus dikumpulkan hari ini sebelum {$deadlineTime}!",
                                '/tasks'
                            );
                            $totalPushed++;
                        }

                        if ($user->wa_notifications_enabled && ! empty($user->phone_number)) {
                            $whatsAppService->sendTaskReminder($user, $task, 'due_today');
                            $totalWA++;
                        }

                        Cache::put($cacheKey, true, now()->endOfDay());
                    }
                }
            }

            // 3. Overdue Tasks
            if ($user->notify_overdue) {
                $tasksOverdue = Task::with('subject')
                    ->where('user_id', $user->id)
                    ->where('status', '!=', 'completed')
                    ->where('deadline', '<', $now)
                    ->get();

                foreach ($tasksOverdue as $task) {
                    $cacheKey = "task_reminder_overdue_{$task->id}_{$todayStr}";
                    if ($force || ! Cache::has($cacheKey)) {
                        $subjectName = $task->subject ? $task->subject->name : 'Mata Pelajaran';

                        if ($user->push_notifications_enabled) {
                            $webPushService->sendToUser(
                                $user,
                                "🚨 Tugas Terlambat: {$task->title}",
                                "Tugas {$subjectName} telah melewati batas waktu. Segera selesaikan!",
                                '/tasks?tab=overdue'
                            );
                            $totalPushed++;
                        }

                        if ($user->wa_notifications_enabled && ! empty($user->phone_number)) {
                            $whatsAppService->sendTaskReminder($user, $task, 'overdue');
                            $totalWA++;
                        }

                        Cache::put($cacheKey, true, now()->endOfDay());
                    }
                }
            }
        }

        $this->info("Pengecekan selesai! Push terkirim: {$totalPushed}, WhatsApp terkirim: {$totalWA}");
        return self::SUCCESS;
    }
}
