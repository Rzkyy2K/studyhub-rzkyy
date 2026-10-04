<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/notifications', [\App\Http\Controllers\Settings\NotificationSettingsController::class, 'edit'])->name('notifications.settings.edit');
    Route::put('settings/notifications/preferences', [\App\Http\Controllers\Settings\NotificationSettingsController::class, 'updatePreferences'])->name('notifications.settings.preferences');
    Route::post('settings/notifications/subscribe', [\App\Http\Controllers\Settings\NotificationSettingsController::class, 'subscribe'])->name('notifications.settings.subscribe');
    Route::post('settings/notifications/unsubscribe', [\App\Http\Controllers\Settings\NotificationSettingsController::class, 'unsubscribe'])->name('notifications.settings.unsubscribe');
    Route::post('settings/notifications/test-push', [\App\Http\Controllers\Settings\NotificationSettingsController::class, 'testPush'])->name('notifications.settings.testPush');
    Route::post('settings/notifications/test-wa', [\App\Http\Controllers\Settings\NotificationSettingsController::class, 'testWhatsApp'])->name('notifications.settings.testWhatsApp');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
