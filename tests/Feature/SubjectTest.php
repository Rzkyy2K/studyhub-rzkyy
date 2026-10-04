<?php

use App\Models\Subject;
use App\Models\User;

test('unauthenticated user cannot access subjects index', function () {
    $response = $this->get(route('subjects.index'));
    $response->assertRedirect(route('login'));
});

test('user can view their own subjects list on subjects page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $subject = Subject::create([
        'user_id' => $user->id,
        'name' => 'Biologi',
        'teacher' => 'Bu Siti',
        'room' => 'Lab Biologi',
    ]);

    $response = $this->get(route('subjects.index'));
    $response->assertOk();
    $response->assertSee('Biologi');
    $response->assertSee('Bu Siti');
    $response->assertSee('Lab Biologi');
});

test('user can create a new subject', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('subjects.store'), [
        'name' => 'Bahasa Inggris',
        'teacher' => 'Mr. John',
        'room' => 'Ruang 4',
    ]);

    $response->assertRedirect();
    $subject = Subject::where('user_id', $user->id)->where('name', 'Bahasa Inggris')->first();
    expect($subject)->not->toBeNull();
    expect($subject->teacher)->toBe('Mr. John');
    expect($subject->room)->toBe('Ruang 4');
});

test('user can update their own subject', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $subject = Subject::create([
        'user_id' => $user->id,
        'name' => 'Kimia Dasar',
        'teacher' => 'Pak Rudi',
        'room' => 'Lab 1',
    ]);

    $response = $this->put(route('subjects.update', $subject), [
        'name' => 'Kimia Lanjutan',
        'teacher' => 'Pak Rudi, M.Si',
        'room' => 'Lab Kimia Baru',
    ]);

    $response->assertRedirect();
    $subject->refresh();
    expect($subject->name)->toBe('Kimia Lanjutan');
    expect($subject->teacher)->toBe('Pak Rudi, M.Si');
    expect($subject->room)->toBe('Lab Kimia Baru');
});

test('user cannot update someone else subject', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $subject = Subject::create([
        'user_id' => $user1->id,
        'name' => 'Fisika User 1',
    ]);

    $this->actingAs($user2);
    $response = $this->put(route('subjects.update', $subject), [
        'name' => 'Hacked Fisika',
    ]);

    $response->assertForbidden();
    $subject->refresh();
    expect($subject->name)->toBe('Fisika User 1');
});

test('user can delete their own subject', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $subject = Subject::create([
        'user_id' => $user->id,
        'name' => 'Geografi',
    ]);

    $response = $this->delete(route('subjects.destroy', $subject));
    $response->assertRedirect();
    expect(Subject::find($subject->id))->toBeNull();
});

test('user cannot delete someone else subject', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $subject = Subject::create([
        'user_id' => $user1->id,
        'name' => 'Sejarah User 1',
    ]);

    $this->actingAs($user2);
    $response = $this->delete(route('subjects.destroy', $subject));
    $response->assertForbidden();
    expect(Subject::find($subject->id))->not->toBeNull();
});
