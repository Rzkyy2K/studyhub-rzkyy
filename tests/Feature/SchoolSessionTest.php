<?php

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use App\Services\SchoolSessionService;
use Carbon\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-10-05 08:00:00'));
});

afterEach(function () {
    Carbon::setTestNow();
});

test('auto initializes user session to teori on or after 2026-10-05', function () {
    $user = User::factory()->create([
        'session_started_at' => null,
        'session_ends_at' => null,
    ]);

    $service = app(SchoolSessionService::class);
    $service->syncUserSession($user);

    $user->refresh();

    expect($user->active_session)->toBe('teori')
        ->and($user->session_started_at->toDateString())->toBe('2026-10-05')
        ->and($user->session_ends_at->toDateString())->toBe('2026-11-05');
});

test('auto rotates session to bengkel after 1 month', function () {
    $user = User::factory()->create([
        'session_started_at' => null,
        'session_ends_at' => null,
    ]);

    $service = app(SchoolSessionService::class);

    // Fast forward to 2026-11-06 (1 day after first month ends)
    Carbon::setTestNow(Carbon::parse('2026-11-06 08:00:00'));

    $service->syncUserSession($user);
    $user->refresh();

    expect($user->active_session)->toBe('bengkel');
});

test('switching view session to bengkel preserves official session validity period', function () {
    $user = User::factory()->create([
        'active_session' => 'teori',
        'view_session' => 'teori',
        'session_started_at' => Carbon::parse('2026-10-05'),
        'session_ends_at' => Carbon::parse('2026-11-05'),
    ]);

    $this->actingAs($user)
        ->post(route('session.switch'), ['session' => 'bengkel'])
        ->assertRedirect();

    $user->refresh();

    expect($user->view_session)->toBe('bengkel')
        ->and($user->active_session)->toBe('teori')
        ->and($user->session_started_at->toDateString())->toBe('2026-10-05')
        ->and($user->session_ends_at->toDateString())->toBe('2026-11-05');
});

test('explicitly starting new cycle resets official session and 1-month countdown', function () {
    $user = User::factory()->create([
        'active_session' => 'teori',
        'session_started_at' => Carbon::parse('2026-10-05'),
        'session_ends_at' => Carbon::parse('2026-11-05'),
    ]);

    // Fast forward 10 days to 2026-10-15
    Carbon::setTestNow(Carbon::parse('2026-10-15 10:00:00'));

    $this->actingAs($user)
        ->post(route('session.startCycle'), ['session' => 'bengkel'])
        ->assertRedirect();

    $user->refresh();

    expect($user->active_session)->toBe('bengkel')
        ->and($user->view_session)->toBe('bengkel')
        ->and($user->session_started_at->toDateString())->toBe('2026-10-15')
        ->and($user->session_ends_at->toDateString())->toBe('2026-11-15');
});

test('extending session adds 7 days to session ends_at', function () {
    $user = User::factory()->create([
        'active_session' => 'teori',
        'session_started_at' => Carbon::parse('2026-10-05'),
        'session_ends_at' => Carbon::parse('2026-11-05'),
    ]);

    $this->actingAs($user)
        ->post(route('session.extend'), ['days' => 7])
        ->assertRedirect();

    $user->refresh();

    expect($user->session_ends_at->toDateString())->toBe('2026-11-12');
});

test('subjects and tasks are segregated by session', function () {
    $user = User::factory()->create([
        'active_session' => 'teori',
        'session_started_at' => Carbon::parse('2026-10-05'),
        'session_ends_at' => Carbon::parse('2026-11-05'),
    ]);

    $teoriSubject = Subject::create([
        'user_id' => $user->id,
        'name' => 'Matematika Wajib',
        'session' => 'teori',
    ]);

    $bengkelSubject = Subject::create([
        'user_id' => $user->id,
        'name' => 'Pemrograman Web RPL',
        'session' => 'bengkel',
    ]);

    $teoriTask = Task::create([
        'user_id' => $user->id,
        'subject_id' => $teoriSubject->id,
        'title' => 'Tugas Aljabar',
        'deadline' => Carbon::parse('2026-10-10 12:00:00'),
        'priority' => 'high',
        'status' => 'pending',
        'session' => 'teori',
    ]);

    $bengkelTask = Task::create([
        'user_id' => $user->id,
        'subject_id' => $bengkelSubject->id,
        'title' => 'Praktikum Database API',
        'deadline' => Carbon::parse('2026-10-12 12:00:00'),
        'priority' => 'high',
        'status' => 'pending',
        'session' => 'bengkel',
    ]);

    // When viewing tasks with default active session (teori)
    $response = $this->actingAs($user)->get(route('tasks.index'));
    $response->assertOk();
    $pageTasks = $response->viewData('page')['props']['tasks'];

    expect(collect($pageTasks)->pluck('id'))->toContain($teoriTask->id)
        ->and(collect($pageTasks)->pluck('id'))->not->toContain($bengkelTask->id);

    // When explicitly filtering for bengkel
    $responseBengkel = $this->actingAs($user)->get(route('tasks.index', ['session' => 'bengkel']));
    $responseBengkel->assertOk();
    $bengkelPageTasks = $responseBengkel->viewData('page')['props']['tasks'];

    expect(collect($bengkelPageTasks)->pluck('id'))->toContain($bengkelTask->id)
        ->and(collect($bengkelPageTasks)->pluck('id'))->not->toContain($teoriTask->id);
});

test('store task assigns session properly', function () {
    $user = User::factory()->create([
        'active_session' => 'teori',
        'session_started_at' => Carbon::parse('2026-10-05'),
        'session_ends_at' => Carbon::parse('2026-11-05'),
    ]);

    $this->actingAs($user)->post(route('tasks.store'), [
        'title' => 'Tugas Mandiri Teori',
        'deadline' => '2026-10-08 10:00:00',
        'priority' => 'medium',
        'status' => 'pending',
        'session' => 'teori',
    ])->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Tugas Mandiri Teori',
        'session' => 'teori',
    ]);
});

test('schedule and todos can be created and filtered by session', function () {
    $user = User::factory()->create([
        'active_session' => 'bengkel',
        'session_started_at' => Carbon::parse('2026-10-05'),
        'session_ends_at' => Carbon::parse('2026-11-05'),
    ]);

    $sub = Subject::create([
        'user_id' => $user->id,
        'name' => 'Bengkel Jaringan',
        'session' => 'bengkel',
    ]);

    // Create schedule
    $this->actingAs($user)->post(route('schedule.store'), [
        'day' => 'monday',
        'subject_id' => $sub->id,
        'start_time' => '08:00',
        'end_time' => '11:00',
        'session' => 'bengkel',
    ])->assertRedirect();

    $this->assertDatabaseHas('schedules', [
        'user_id' => $user->id,
        'session' => 'bengkel',
    ]);

    // Create todo
    $this->actingAs($user)->post(route('todos.store'), [
        'title' => 'Beli kabel LAN crimping',
        'priority' => 'medium',
        'session' => 'bengkel',
    ])->assertRedirect();

    $this->assertDatabaseHas('todos', [
        'user_id' => $user->id,
        'title' => 'Beli kabel LAN crimping',
        'session' => 'bengkel',
    ]);
});
