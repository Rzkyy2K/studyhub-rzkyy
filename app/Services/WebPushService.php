<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    protected ?WebPush $webPush = null;

    public function __construct()
    {
        $publicKey = env('VAPID_PUBLIC_KEY');
        $privateKey = env('VAPID_PRIVATE_KEY');
        $subject = env('VAPID_SUBJECT', 'mailto:admin@studyhub.local');

        if ($publicKey && $privateKey) {
            $auth = [
                'VAPID' => [
                    'subject' => $subject,
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ];
            $this->webPush = new WebPush($auth);
        }
    }

    /**
     * Check if WebPush service is configured.
     */
    public function isConfigured(): bool
    {
        return $this->webPush !== null;
    }

    /**
     * Get VAPID public key.
     */
    public function getPublicKey(): ?string
    {
        return env('VAPID_PUBLIC_KEY');
    }

    /**
     * Send notification to all subscriptions of a user.
     *
     * @return array{sent: int, failed: int}
     */
    public function sendToUser(User $user, string $title, string $body, ?string $url = null, ?string $icon = null): array
    {
        if (! $this->isConfigured()) {
            Log::warning('WebPushService: VAPID keys not configured.');
            return ['sent' => 0, 'failed' => 0];
        }

        $subscriptions = $user->pushSubscriptions;
        if ($subscriptions->isEmpty()) {
            return ['sent' => 0, 'failed' => 0];
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($subscriptions as $subscription) {
            $success = $this->sendToSubscription($subscription, $title, $body, $url, $icon);
            if ($success) {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        return ['sent' => $sentCount, 'failed' => $failedCount];
    }

    /**
     * Send notification to a specific subscription.
     */
    public function sendToSubscription(
        PushSubscription $subscription,
        string $title,
        string $body,
        ?string $url = null,
        ?string $icon = null
    ): bool {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'icon' => $icon ?: '/apple-touch-icon.png',
                'badge' => '/favicon.svg',
                'data' => [
                    'url' => $url ?: '/dashboard',
                ],
            ], JSON_UNESCAPED_SLASHES);

            $webPushSub = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
            ]);

            $report = $this->webPush->sendOneNotification($webPushSub, $payload);

            if ($report->isSuccess()) {
                return true;
            }

            Log::warning("WebPush notification failed: {$report->getReason()}", [
                'endpoint' => $subscription->endpoint,
                'response' => $report->getResponse()?->getBody()?->getContents(),
            ]);

            // If subscription expired or not found, remove it
            if ($report->isSubscriptionExpired()) {
                $subscription->delete();
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('WebPushService exception: ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
            ]);
            return false;
        }
    }
}
