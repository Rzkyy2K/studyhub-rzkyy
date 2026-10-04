<?php

use App\Models\Subject;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('completed tasks are hidden from active tasks list and saved to history', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $subject = Subject::create([
        'user_id' => $user->id,
        'name' => 'Pemrograman Web',
    ]);

    $task = Task::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'title' => 'Tugas Laravel Inertia',
        'deadline' => Carbon::tomorrow(),
        'priority' => 'high',
        'status' => 'pending',
    ]);

    // Active tasks list contains the task
    $response = $this->get(route('tasks.index'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Tasks/Index')
        ->has('tasks', 1)
        ->where('tasks.0.id', $task->id)
    );

    // Toggle complete
    $this->patch(route('tasks.toggle', $task))->assertRedirect();
    $task->refresh();
    expect($task->status)->toBe('completed');
    expect($task->completed_at)->not->toBeNull();

    // Active tasks list NO LONGER contains the completed task
    $activeResponse = $this->get(route('tasks.index'));
    $activeResponse->assertOk();
    $activeResponse->assertInertia(fn (Assert $page) => $page
        ->component('Tasks/Index')
        ->has('tasks', 0)
    );

    // History page CONTAINS the completed task
    $historyResponse = $this->get(route('tasks.history'));
    $historyResponse->assertOk();
    $historyResponse->assertInertia(fn (Assert $page) => $page
        ->component('Tasks/History')
        ->has('tasks', 1)
        ->where('tasks.0.id', $task->id)
    );

    // Restore task from history
    $this->patch(route('tasks.toggle', $task))->assertRedirect();
    $task->refresh();
    expect($task->status)->toBe('pending');
    expect($task->completed_at)->toBeNull();

    // Active tasks list CONTAINS the task again
    $restoredResponse = $this->get(route('tasks.index'));
    $restoredResponse->assertOk();
    $restoredResponse->assertInertia(fn (Assert $page) => $page
        ->component('Tasks/Index')
        ->has('tasks', 1)
        ->where('tasks.0.id', $task->id)
    );
});

test('completed tasks older than 7 days are automatically pruned', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Recent completed task (3 days ago)
    $recentTask = Task::create([
        'user_id' => $user->id,
        'title' => 'Tugas Selesai Baru',
        'deadline' => Carbon::now()->subDays(3),
        'priority' => 'low',
        'status' => 'completed',
        'completed_at' => Carbon::now()->subDays(3),
    ]);

    // Expired completed task (8 days ago)
    $expiredTask = Task::create([
        'user_id' => $user->id,
        'title' => 'Tugas Selesai Lama',
        'deadline' => Carbon::now()->subDays(8),
        'priority' => 'low',
        'status' => 'completed',
        'completed_at' => Carbon::now()->subDays(8),
    ]);

    // Visiting history triggers auto-pruning
    $response = $this->get(route('tasks.history'));
    $response->assertOk();

    expect(Task::find($recentTask->id))->not->toBeNull();
    expect(Task::find($expiredTask->id))->toBeNull();
});

test('user can reset all completed tasks history', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Task::create([
        'user_id' => $user->id,
        'title' => 'Tugas Selesai 1',
        'deadline' => Carbon::now()->subDay(),
        'priority' => 'low',
        'status' => 'completed',
        'completed_at' => Carbon::now()->subDay(),
    ]);

    $this->post(route('tasks.history.reset'))->assertRedirect();

    expect(Task::where('user_id', $user->id)->where('status', 'completed')->count())->toBe(0);
});

test('user can update task status via tasks.status endpoint', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Tugas Desain Grafis',
        'deadline' => Carbon::tomorrow(),
        'priority' => 'medium',
        'status' => 'pending',
    ]);

    // Change to in_progress
    $this->patch(route('tasks.status', $task), ['status' => 'in_progress'])->assertRedirect();
    $task->refresh();
    expect($task->status)->toBe('in_progress');
    expect($task->completed_at)->toBeNull();

    // Change to completed
    $this->patch(route('tasks.status', $task), ['status' => 'completed'])->assertRedirect();
    $task->refresh();
    expect($task->status)->toBe('completed');
    expect($task->completed_at)->not->toBeNull();
});

test('days_until_reset outputs integer days without decimals', function () {
    $task = new Task([
        'status' => 'completed',
        'completed_at' => Carbon::now(),
    ]);

    expect($task->days_until_reset)->toBe('6 hari lagi');
});

test('urgency_label outputs integer numbers without decimals', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-05 08:00:00'));

    $taskHours = new Task([
        'status' => 'pending',
        'deadline' => Carbon::parse('2026-10-05 11:15:00'), // ~3 hours away
    ]);
    expect($taskHours->urgency_label)->toBe('Deadline tinggal 3 jam');

    $taskMins = new Task([
        'status' => 'pending',
        'deadline' => Carbon::parse('2026-10-05 08:45:00'), // 45 minutes away
    ]);
    expect($taskMins->urgency_label)->toBe('Deadline tinggal 45 menit');
});

