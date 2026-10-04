<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->after('email');
            $table->boolean('wa_notifications_enabled')->default(false)->after('phone_number');
            $table->boolean('push_notifications_enabled')->default(true)->after('wa_notifications_enabled');
            $table->boolean('notify_h_minus_1')->default(true)->after('push_notifications_enabled');
            $table->boolean('notify_due_today')->default(true)->after('notify_h_minus_1');
            $table->boolean('notify_overdue')->default(true)->after('notify_due_today');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number',
                'wa_notifications_enabled',
                'push_notifications_enabled',
                'notify_h_minus_1',
                'notify_due_today',
                'notify_overdue',
            ]);
        });
    }
};
