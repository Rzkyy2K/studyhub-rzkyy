<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('active_session', 20)->default('teori')->after('class');
            $table->date('session_started_at')->nullable()->after('active_session');
            $table->date('session_ends_at')->nullable()->after('session_started_at');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('session', 20)->default('teori')->after('room');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('session', 20)->default('teori')->after('status');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->string('session', 20)->default('teori')->after('room');
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->string('session', 20)->default('teori')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['active_session', 'session_started_at', 'session_ends_at']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('session');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('session');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('session');
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn('session');
        });
    }
};
