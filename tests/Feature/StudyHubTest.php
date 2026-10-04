<?php

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;

test('new user can register with class field and is redirected to dashboard', function () {
    $response = $this->post(route('register'), [
        'name' => 'Budi Pratama',
        'email' => 'budi@studyhub.test',
        'class' => 'XII RPL 2',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = User::where('email', 'budi@studyhub.test')->first();
    expect($user)->not->toBeNull();
    expect($user->class)->toBe('XII RPL 2');
});

test('user can log in and view dashboard with summary stats and focus tasks', function () {
    $user = User::factory()->create(['class' => 'XI MIPA 1']);
    $this->actingAs($user);

    $subject = Subject::create([
        'user_id' => $user->id,
        'name' => 'Matematika',
    ]);

    Task::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'title' => 'Tugas Aljabar',
        'deadline' => Carbon::now()->addHours(3),
        'priority' => 'high',
        'status' => 'pending',
    ]);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee('Matematika');
    $response->assertSee('Tugas Aljabar');
});

test('user can create, edit, toggle, and delete their own task', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create task
    $response = $this->post(route('tasks.store'), [
        'title' => 'Tugas Fisika Dinamika',
        'new_subject_name' => 'Fisika',
        'description' => 'Mengerjakan soal bab 3',
        'deadline' => Carbon::tomorrow()->toDateTimeString(),
        'priority' => 'medium',
        'status' => 'pending',
    ]);

    $response->assertRedirect();
    $task = Task::where('user_id', $user->id)->first();
    expect($task)->not->toBeNull();
    expect($task->title)->toBe('Tugas Fisika Dinamika');
    expect($task->subject->name)->toBe('Fisika');

    // Toggle status
    $this->patch(route('tasks.toggle', $task))->assertRedirect();
    $task->refresh();
    expect($task->status)->toBe('completed');

    // Update task
    $this->put(route('tasks.update', $task), [
        'title' => 'Tugas Fisika Terupdate',
        'deadline' => Carbon::tomorrow()->addDay()->toDateTimeString(),
        'priority' => 'high',
        'status' => 'in_progress',
    ])->assertRedirect();
    $task->refresh();
    expect($task->title)->toBe('Tugas Fisika Terupdate');
    expect($task->priority)->toBe('high');

    // Delete task
    $this->delete(route('tasks.destroy', $task))->assertRedirect();
    expect(Task::find($task->id))->toBeNull();
});

test('user cannot access or modify another users task', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $task1 = Task::create([
        'user_id' => $user1->id,
        'title' => 'Tugas Rahasia User 1',
        'deadline' => Carbon::tomorrow(),
        'priority' => 'low',
        'status' => 'pending',
    ]);

    $this->actingAs($user2);

    $this->put(route('tasks.update', $task1), [
        'title' => 'Hacked title',
        'deadline' => Carbon::tomorrow()->toDateTimeString(),
        'priority' => 'high',
        'status' => 'pending',
    ])->assertForbidden();

    $this->delete(route('tasks.destroy', $task1))->assertForbidden();
});

test('schedule validates end time after start time', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('schedule.store'), [
        'day' => 'monday',
        'new_subject_name' => 'Kimia',
        'start_time' => '10:00',
        'end_time' => '09:00', // Invalid! End earlier than start
    ]);

    $response->assertSessionHasErrors('end_time');
});

test('schedule only allows monday through friday and rejects saturday', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Saturday is rejected
    $response = $this->post(route('schedule.store'), [
        'day' => 'saturday',
        'new_subject_name' => 'Robotika',
        'start_time' => '08:00',
        'end_time' => '10:00',
    ]);
    $response->assertSessionHasErrors('day');

    // Friday is accepted
    $responseValid = $this->post(route('schedule.store'), [
        'day' => 'friday',
        'new_subject_name' => 'Robotika',
        'start_time' => '08:00',
        'end_time' => '10:00',
    ]);
    $responseValid->assertSessionHasNoErrors();
    expect(Schedule::where('user_id', $user->id)->where('day', 'friday')->exists())->toBeTrue();
});

test('user can manage schedule and delete with ownership protection', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->actingAs($user1);
    $this->post(route('schedule.store'), [
        'day' => 'wednesday',
        'new_subject_name' => 'Biologi',
        'start_time' => '08:00',
        'end_time' => '09:30',
        'room' => 'Lab Biologi',
    ])->assertRedirect();

    $sch = Schedule::where('user_id', $user1->id)->first();
    expect($sch)->not->toBeNull();

    // User 2 cannot delete
    $this->actingAs($user2);
    $this->delete(route('schedule.destroy', $sch))->assertForbidden();

    // User 1 can delete
    $this->actingAs($user1);
    $this->delete(route('schedule.destroy', $sch))->assertRedirect();
    expect(Schedule::find($sch->id))->toBeNull();
});

test('user can manage todos and toggle completion', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('todos.store'), [
        'title' => 'Beli kalkulator scientific',
        'priority' => 'high',
        'due_date' => Carbon::today()->toDateString(),
    ])->assertRedirect();

    $todo = Todo::where('user_id', $user->id)->first();
    expect($todo)->not->toBeNull();
    expect($todo->status)->toBe('pending');

    // Toggle complete
    $this->patch(route('todos.toggle', $todo))->assertRedirect();
    $todo->refresh();
    expect($todo->status)->toBe('completed');

    // Toggle back
    $this->patch(route('todos.toggle', $todo))->assertRedirect();
    $todo->refresh();
    expect($todo->status)->toBe('pending');

    // Delete
    $this->delete(route('todos.destroy', $todo))->assertRedirect();
    expect(Todo::find($todo->id))->toBeNull();
});
