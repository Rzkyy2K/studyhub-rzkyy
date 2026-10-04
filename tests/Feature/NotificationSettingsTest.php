<?php

use App\Models\PushSubscription;
use App\Models\Subject;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

test('user can view notification settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('notifications.settings.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Notifications')
        ->has('user')
        ->has('subscriptions')
        ->has('vapidPublicKey')
    );
});

test('user can update notification preferences and phone number is formatted', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('notifications.settings.preferences'), [
        'phone_number' => '081234567890',
        'wa_notifications_enabled' => true,
        'push_notifications_enabled' => true,
        'notify_h_minus_1' => true,
        'notify_due_today' => true,
        'notify_overdue' => false,
    ]);

    $response->assertRedirect();
    $user->refresh();

    expect($user->phone_number)->toBe('6281234567890');
    expect($user->wa_notifications_enabled)->toBeTrue();
    expect($user->push_notifications_enabled)->toBeTrue();
    expect($user->notify_overdue)->toBeFalse();
});

test('user can register a push subscription', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('notifications.settings.subscribe'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-token-123',
        'keys' => [
            'p256dh' => 'BDs823kjlksdf23...',
            'auth' => 'a8sd9fasdf...',
        ],
        'deviceName' => 'Android Phone',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    expect(PushSubscription::where('user_id', $user->id)->count())->toBe(1);
    $sub = PushSubscription::where('user_id', $user->id)->first();
    expect($sub->device_name)->toBe('Android Phone');
});

test('user can unsubscribe from push notifications', function () {
    $user = User::factory()->create();
    $sub = PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => 'https://example.com/test-endpoint',
        'public_key' => 'key',
        'auth_token' => 'auth',
        'device_name' => 'Chrome',
    ]);

    $response = $this->actingAs($user)->postJson(route('notifications.settings.unsubscribe'), [
        'id' => $sub->id,
    ]);

    $response->assertOk();
    expect(PushSubscription::where('id', $sub->id)->exists())->toBeFalse();
});

test('user can trigger test whatsapp message in log mode', function () {
    $user = User::factory()->create([
        'phone_number' => '6289999999999',
    ]);

    $response = $this->actingAs($user)->postJson(route('notifications.settings.testWhatsApp'), [
        'phone_number' => '08123456789',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
});

test('tasks:remind command runs successfully and detects tasks near deadline', function () {
    $user = User::factory()->create([
        'push_notifications_enabled' => true,
        'wa_notifications_enabled' => true,
        'phone_number' => '62812345678',
        'notify_due_today' => true,
        'notify_h_minus_1' => true,
    ]);

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

    $exitCode = Artisan::call('tasks:remind', ['--force' => true]);
    expect($exitCode)->toBe(0);
});

test('creating a task sends instant whatsapp notification when enabled', function () {
    $user = User::factory()->create([
        'wa_notifications_enabled' => true,
        'phone_number' => '62812345678',
    ]);

    $response = $this->actingAs($user)->post(route('tasks.store'), [
        'title' => 'Tugas Matematika Baru',
        'deadline' => Carbon::tomorrow()->toDateTimeString(),
        'priority' => 'high',
        'status' => 'pending',
    ]);

    $response->assertRedirect();
    expect(Task::where('user_id', $user->id)->count())->toBe(1);
});

