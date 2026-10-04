<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $provider;
    protected ?string $token;
    protected string $apiUrl;

    public function __construct()
    {
        $this->provider = env('WA_GATEWAY_PROVIDER', 'log');
        $this->token = env('WA_API_TOKEN');
        $this->apiUrl = env('WA_API_URL', 'https://api.fonnte.com/send');
    }

    /**
     * Normalize Indonesian phone number into 628xxx format.
     */
    public function normalizePhoneNumber(?string $number): ?string
    {
        if (! $number) {
            return null;
        }

        // Remove spaces, hyphens, and plus signs
        $cleaned = preg_replace('/[^0-9]/', '', $number);

        if (empty($cleaned)) {
            return null;
        }

        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Send a WhatsApp message to a phone number.
     *
     * @return array{success: bool, message: string}
     */
    public function send(string $phoneNumber, string $message): array
    {
        $target = $this->normalizePhoneNumber($phoneNumber);

        if (! $target) {
            return [
                'success' => false,
                'message' => 'Nomor WhatsApp tidak valid.',
            ];
        }

        // 1. Log mode (Default local development / testing without gateway account)
        if ($this->provider === 'log' || empty($this->token)) {
            Log::info("=== [SIMULASI WHATSAPP] Dikirim ke: {$target} ===");
            Log::info($message);
            Log::info("================================================");

            return [
                'success' => true,
                'message' => "Mode Simulasi (Log): Pesan pengingat berhasil dibuat untuk nomor {$target}.",
            ];
        }

        // 2. Fonnte Provider
        if ($this->provider === 'fonnte') {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $this->token,
                ])->post($this->apiUrl, [
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'message' => 'Pesan WhatsApp berhasil dikirim ke ' . $target,
                    ];
                }

                Log::error('Fonnte WhatsApp API error: ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'Gagal mengirim pesan via Fonnte: ' . ($response->json('reason') ?? $response->body()),
                ];
            } catch (\Throwable $e) {
                Log::error('WhatsApp send exception: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Koneksi ke gateway WhatsApp gagal: ' . $e->getMessage(),
                ];
            }
        }

        // 3. Wablas Provider
        if ($this->provider === 'wablas') {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $this->token,
                ])->post($this->apiUrl, [
                    'phone' => $target,
                    'message' => $message,
                ]);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'message' => 'Pesan WhatsApp berhasil dikirim ke ' . $target,
                    ];
                }

                Log::error('Wablas WhatsApp API error: ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'Gagal mengirim pesan via Wablas.',
                ];
            } catch (\Throwable $e) {
                Log::error('WhatsApp send exception: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Koneksi ke gateway WhatsApp gagal: ' . $e->getMessage(),
                ];
            }
        }

        // 4. Custom Webhook
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->post($this->apiUrl, [
                'phone' => $target,
                'message' => $message,
            ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Pesan terkirim.' : 'Gagal mengirim pesan.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format a task reminder message in clean Indonesian markdown with emojis.
     */
    public function formatTaskReminder(User $user, Task $task, string $reminderType): string
    {
        $subjectName = $task->subject ? $task->subject->name : 'Mata Pelajaran';
        $deadlineStr = $task->deadline ? $task->deadline->translatedFormat('l, d F Y - H:i') : '-';
        $appUrl = config('app.url', 'http://localhost');

        $typeLabel = match ($reminderType) {
            'due_tomorrow' => '⏳ *PENGINGAT: DEADLINE BESOK*',
            'due_today' => '⚠️ *PERINGATAN: DEADLINE HARI INI*',
            'overdue' => '🚨 *PERINGATAN: TUGAS TELAH MELEWATI DEADLINE*',
            default => '📚 *PENGINGAT TUGAS STUDYHUB*',
        };

        return "{$typeLabel}\n\n"
            . "Halo *{$user->name}*! 👋\n"
            . "Ada tugas penting yang memerlukan perhatianmu:\n\n"
            . "📌 *Judul Tugas:* {$task->title}\n"
            . "📖 *Mata Pelajaran:* {$subjectName}\n"
            . "⏰ *Batas Waktu:* {$deadlineStr}\n"
            . "📊 *Status:* " . ($task->status === 'in_progress' ? 'Sedang Dikerjakan' : 'Belum Dikerjakan') . "\n\n"
            . ($task->description ? "📝 *Catatan:* {$task->description}\n\n" : "")
            . "Semangat belajarnya, jangan tunda lagi ya! 💪\n"
            . "🔗 Buka StudyHub: {$appUrl}/tasks";
    }

    /**
     * Send task reminder to a user if WA notifications are enabled.
     */
    public function sendTaskReminder(User $user, Task $task, string $reminderType): bool
    {
        if (! $user->wa_notifications_enabled || empty($user->phone_number)) {
            return false;
        }

        $message = $this->formatTaskReminder($user, $task, $reminderType);
        $result = $this->send($user->phone_number, $message);

        return $result['success'];
    }

    /**
     * Format a message when a new task is created.
     */
    public function formatNewTaskMessage(User $user, Task $task): string
    {
        $subjectName = $task->subject ? $task->subject->name : 'Mata Pelajaran';
        $deadlineStr = $task->deadline ? $task->deadline->translatedFormat('l, d F Y - H:i') : '-';
        $appUrl = config('app.url', 'http://localhost');

        return "🎉 *TUGAS BARU STUDYHUB BERHASIL DITAMBAHKAN*\n\n"
            . "Halo *{$user->name}*! 👋\n"
            . "Tugas baru telah berhasil dicatat ke sistem StudyHub:\n\n"
            . "📌 *Judul Tugas:* {$task->title}\n"
            . "📖 *Mata Pelajaran:* {$subjectName}\n"
            . "⏰ *Batas Waktu (Deadline):* {$deadlineStr}\n"
            . "🎯 *Prioritas:* " . ucfirst($task->priority) . "\n\n"
            . ($task->description ? "📝 *Catatan:* {$task->description}\n\n" : "")
            . "Yuk mulai dicicil pengerjaannya dari sekarang! Semangat! 💪\n"
            . "🔗 Buka Detail Tugas: {$appUrl}/tasks";
    }

    /**
     * Send new task notification to user via WhatsApp.
     */
    public function sendNewTaskNotification(User $user, Task $task): bool
    {
        if (! $user->wa_notifications_enabled || empty($user->phone_number)) {
            return false;
        }

        $message = $this->formatNewTaskMessage($user, $task);
        $result = $this->send($user->phone_number, $message);

        return $result['success'];
    }
}
