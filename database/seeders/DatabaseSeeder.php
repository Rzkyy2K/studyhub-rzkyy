<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'rizky@studyhub.test'],
            [
                'name' => 'Rizky',
                'password' => Hash::make('password'),
                'class' => 'XII RPL 1',
                'email_verified_at' => now(),
            ]
        );

        // Subjects
        $subWeb = Subject::create([
            'user_id' => $user->id,
            'name' => 'Pemrograman Web',
            'teacher' => 'Pak Budi',
            'room' => 'Lab RPL',
        ]);

        $subDb = Subject::create([
            'user_id' => $user->id,
            'name' => 'Basis Data',
            'teacher' => 'Pak Doni',
            'room' => 'Lab Komputer',
        ]);

        $subMath = Subject::create([
            'user_id' => $user->id,
            'name' => 'Matematika',
            'teacher' => 'Bu Ratna',
            'room' => 'Ruang 3',
        ]);

        $subEng = Subject::create([
            'user_id' => $user->id,
            'name' => 'Bahasa Inggris',
            'teacher' => 'Miss Sarah',
            'room' => 'Ruang 12',
        ]);

        $subPhy = Subject::create([
            'user_id' => $user->id,
            'name' => 'Fisika',
            'teacher' => 'Pak Hendra',
            'room' => 'Lab Fisika',
        ]);

        // Tasks
        Task::create([
            'user_id' => $user->id,
            'subject_id' => $subWeb->id,
            'title' => 'Membuat CRUD Laravel',
            'description' => 'Selesaikan implementasi CRUD dengan otentikasi dan migrasi database lengkap.',
            'deadline' => Carbon::today()->setHour(20)->setMinute(0)->setSecond(0),
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        Task::create([
            'user_id' => $user->id,
            'subject_id' => $subDb->id,
            'title' => 'Membuat ERD Database Toko Online',
            'description' => 'Rancang relasi minimal 6 tabel: users, products, categories, orders, order_items, payments.',
            'deadline' => Carbon::tomorrow()->setHour(20)->setMinute(0)->setSecond(0),
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        Task::create([
            'user_id' => $user->id,
            'subject_id' => $subMath->id,
            'title' => 'Latihan Soal Matriks dan Determinan',
            'description' => 'Kerjakan halaman 45 nomor 1 sampai 10 di buku cetak.',
            'deadline' => Carbon::now()->addDays(3)->setHour(14)->setMinute(0)->setSecond(0),
            'priority' => 'low',
            'status' => 'pending',
        ]);

        Task::create([
            'user_id' => $user->id,
            'subject_id' => $subEng->id,
            'title' => 'Essay Narrative Text - Folk Tale',
            'description' => 'Tulis cerita rakyat minimal 300 kata dalam bahasa Inggris formal.',
            'deadline' => Carbon::yesterday()->setHour(23)->setMinute(59)->setSecond(0),
            'priority' => 'high',
            'status' => 'pending',
        ]);

        Task::create([
            'user_id' => $user->id,
            'subject_id' => $subPhy->id,
            'title' => 'Laporan Praktikum Gerak Lurus Beraturan',
            'description' => 'Laporan hasil pengukuran ticker timer kelompok 4.',
            'deadline' => Carbon::yesterday()->setHour(17)->setMinute(0)->setSecond(0),
            'priority' => 'medium',
            'status' => 'completed',
        ]);

        // Schedules
        $days = [
            'monday' => [
                ['sub' => $subWeb, 'start' => '08:00', 'end' => '09:30', 'room' => 'Lab RPL'],
                ['sub' => $subMath, 'start' => '10:00', 'end' => '11:30', 'room' => 'Ruang 3'],
                ['sub' => $subDb, 'start' => '13:00', 'end' => '14:30', 'room' => 'Lab Komputer'],
            ],
            'tuesday' => [
                ['sub' => $subEng, 'start' => '08:00', 'end' => '09:30', 'room' => 'Ruang 12'],
                ['sub' => $subPhy, 'start' => '10:00', 'end' => '11:30', 'room' => 'Lab Fisika'],
            ],
            'wednesday' => [
                ['sub' => $subWeb, 'start' => '08:00', 'end' => '09:30', 'room' => 'Lab RPL'],
                ['sub' => $subDb, 'start' => '10:00', 'end' => '12:00', 'room' => 'Lab Komputer'],
            ],
            'thursday' => [
                ['sub' => $subMath, 'start' => '08:00', 'end' => '10:00', 'room' => 'Ruang 3'],
                ['sub' => $subPhy, 'start' => '10:30', 'end' => '12:00', 'room' => 'Lab Fisika'],
            ],
            'friday' => [
                ['sub' => $subEng, 'start' => '07:30', 'end' => '09:00', 'room' => 'Ruang 12'],
                ['sub' => $subWeb, 'start' => '09:15', 'end' => '11:00', 'room' => 'Lab RPL'],
            ],
        ];

        foreach ($days as $day => $items) {
            foreach ($items as $item) {
                Schedule::create([
                    'user_id' => $user->id,
                    'subject_id' => $item['sub']->id,
                    'day' => $day,
                    'start_time' => $item['start'],
                    'end_time' => $item['end'],
                    'room' => $item['room'],
                ]);
            }
        }

        // Todos
        Todo::create([
            'user_id' => $user->id,
            'title' => 'Review materi HTML & CSS dasar',
            'priority' => 'low',
            'due_date' => Carbon::today(),
            'status' => 'completed',
        ]);

        Todo::create([
            'user_id' => $user->id,
            'title' => 'Push project StudyHub ke GitHub',
            'priority' => 'high',
            'due_date' => Carbon::today(),
            'status' => 'pending',
        ]);

        Todo::create([
            'user_id' => $user->id,
            'title' => 'Belajar konsep Eloquent Relationship Laravel',
            'priority' => 'medium',
            'due_date' => Carbon::tomorrow(),
            'status' => 'pending',
        ]);

        Todo::create([
            'user_id' => $user->id,
            'title' => 'Persiapkan perlengkapan tugas untuk besok',
            'priority' => 'medium',
            'due_date' => Carbon::tomorrow(),
            'status' => 'pending',
        ]);

        Todo::create([
            'user_id' => $user->id,
            'title' => 'Beli kertas folio dan binder catatan',
            'priority' => 'low',
            'due_date' => Carbon::now()->addDays(2),
            'status' => 'completed',
        ]);

        app(\App\Services\NotificationService::class)->syncForUser($user);
    }
}
