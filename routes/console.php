<?php

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $oneWeekAgo = Carbon::now()->subDays(7);
    Task::where('status', 'completed')
        ->where(function ($q) use ($oneWeekAgo) {
            $q->where('completed_at', '<', $oneWeekAgo)
              ->orWhere(function ($sub) use ($oneWeekAgo) {
                  $sub->whereNull('completed_at')
                      ->where('updated_at', '<', $oneWeekAgo);
              });
        })
        ->delete();
})->daily()->name('prune-completed-tasks');

Schedule::command('tasks:remind')->hourly()->name('send-task-deadline-reminders');

