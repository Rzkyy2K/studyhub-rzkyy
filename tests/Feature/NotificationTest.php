<?php

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;

test('notifications are generated dynamically for overdue and upcoming tasks', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $subject = Subject::create([
        'user_id' => $user->id,
        'name' => 'Fisika',
    ]);

    // Overdue task
    Task::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'title' => 'Tugas Vektor',
        'deadline' => Carbon::yesterday(),
        'priority' => 'high',
        'status' => 'pending',
    ]);

    // Task due today
    Task::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'title' => 'Tugas Dinamika',
        'deadline' => Carbon::now()->addHours(2),
        'priority' => 'high',
        'status' => 'pending',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-10-05 07:00:00')); // Monday
    // Schedule today
    $currentDay = strtolower(Carbon::now()->format('l'));
    Schedule::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'day' => $currentDay,
        'start_time' => '08:00',
        'end_time' => '09:30',
        'room' => 'Lab Fisika',
    ]);

    // Todo
    Todo::create([
        'user_id' => $user->id,
        'title' => 'Beli kalkulator',
        'priority' => 'low',
        'status' => 'pending',
    ]);

    $response = $this->getJson(route('notifications.index'));
    $response->assertOk();
    $response->assertJsonStructure(['notifications', 'unread_count']);

    $notifications = $response->json('notifications');
    expect(count($notifications))->toBeGreaterThanOrEqual(3);

    $titles = collect($notifications)->pluck('title')->implode(' ');
    expect($titles)->toContain('Tugas Vektor');
    expect($titles)->toContain('Tugas Dinamika');
});

test('user can mark single notification as read', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $notif = UserNotification::create([
        'user_id' => $user->id,
        'title' => 'Pengingat Belajar',
        'message' => 'Jangan lupa review materi.',
        'type' => 'info',
    ]);

    expect($notif->read_at)->toBeNull();

    $this->patchJson(route('notifications.read', $notif))->assertOk();

    $notif->refresh();
    expect($notif->read_at)->not->toBeNull();
    expect($notif->is_read)->toBeTrue();
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    UserNotification::create([
        'user_id' => $user->id,
        'title' => 'Notif 1',
        'message' => 'Pesan 1',
        'type' => 'info',
    ]);

    UserNotification::create([
        'user_id' => $user->id,
        'title' => 'Notif 2',
        'message' => 'Pesan 2',
        'type' => 'warning',
    ]);

    $this->postJson(route('notifications.markAllRead'))->assertOk();

    $unreadCount = UserNotification::where('user_id', $user->id)->whereNull('read_at')->count();
    expect($unreadCount)->toBe(0);
});

test('user can delete their notification and cannot delete others notification', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $notif1 = UserNotification::create([
        'user_id' => $user1->id,
        'title' => 'Notif User 1',
        'message' => 'Pesan Rahasia',
        'type' => 'info',
    ]);

    // User 2 cannot delete user 1's notification
    $this->actingAs($user2);
    $this->deleteJson(route('notifications.destroy', $notif1))->assertForbidden();

    // User 1 can delete
    $this->actingAs($user1);
    $this->deleteJson(route('notifications.destroy', $notif1))->assertOk();
    expect(UserNotification::find($notif1->id))->toBeNull();
});

test('user can clear all notifications', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    UserNotification::create([
        'user_id' => $user->id,
        'title' => 'Notif 1',
        'message' => 'Pesan 1',
        'type' => 'info',
    ]);

    $this->deleteJson(route('notifications.clearAll'))->assertOk();

    expect(UserNotification::where('user_id', $user->id)->count())->toBe(0);
});

test('task notifications are automatically removed when task is marked completed via toggle', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Tugas Matematika',
        'deadline' => Carbon::now()->addHours(3),
        'priority' => 'high',
        'status' => 'pending',
    ]);

    // Fetch notifications so sync runs
    $this->getJson(route('notifications.index'))->assertOk();

    // Verify notification was generated for this task
    $notifCount = UserNotification::where('user_id', $user->id)
        ->where('key', 'like', "task_due_today_{$task->id}_%")
        ->count();
    expect($notifCount)->toBe(1);

    // Now toggle task to completed
    $this->patch(route('tasks.toggle', $task))->assertRedirect();

    $task->refresh();
    expect($task->status)->toBe('completed');

    // Verify task notification was immediately cleaned up
    $notifCountAfter = UserNotification::where('user_id', $user->id)
        ->where('key', 'like', "%_{$task->id}_%")
        ->count();
    expect($notifCountAfter)->toBe(0);

    // Subsequent sync should also not bring it back
    $this->getJson(route('notifications.index'))->assertOk();
    $notifCountAfterSync = UserNotification::where('user_id', $user->id)
        ->where('key', 'like', "%_{$task->id}_%")
        ->count();
    expect($notifCountAfterSync)->toBe(0);
});

test('task notifications are automatically removed when task status is updated to completed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Tugas Kimia',
        'deadline' => Carbon::yesterday(),
        'priority' => 'high',
        'status' => 'pending',
    ]);

    // Generate notifications
    $this->getJson(route('notifications.index'))->assertOk();

    expect(UserNotification::where('user_id', $user->id)->where('key', 'like', "%_{$task->id}_%")->count())->toBe(1);

    // Update status to completed
    $this->patch(route('tasks.status', $task), ['status' => 'completed'])->assertRedirect();

    expect(UserNotification::where('user_id', $user->id)->where('key', 'like', "%_{$task->id}_%")->count())->toBe(0);
});

test('task notifications are cleaned up when task is deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Tugas Biologi',
        'deadline' => Carbon::now()->addHours(1),
        'priority' => 'medium',
        'status' => 'pending',
    ]);

    $this->getJson(route('notifications.index'))->assertOk();
    expect(UserNotification::where('user_id', $user->id)->where('key', 'like', "%_{$task->id}_%")->count())->toBe(1);

    $this->delete(route('tasks.destroy', $task))->assertRedirect();

    expect(UserNotification::where('user_id', $user->id)->where('key', 'like', "%_{$task->id}_%")->count())->toBe(0);
});

