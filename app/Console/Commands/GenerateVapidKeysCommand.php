<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webpush:generate-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate VAPID public and private keys for Web Push Notifications and save them in .env';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating VAPID keys...');

        $keys = VAPID::createVapidKeys();
        $publicKey = $keys['publicKey'];
        $privateKey = $keys['privateKey'];

        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->error('.env file does not exist.');
            return self::FAILURE;
        }

        $envContent = file_get_contents($envPath);

        // Update or append VAPID_PUBLIC_KEY
        if (str_contains($envContent, 'VAPID_PUBLIC_KEY=')) {
            $envContent = preg_replace('/^VAPID_PUBLIC_KEY=.*$/m', "VAPID_PUBLIC_KEY={$publicKey}", $envContent);
        } else {
            $envContent .= "\nVAPID_PUBLIC_KEY={$publicKey}";
        }

        // Update or append VAPID_PRIVATE_KEY
        if (str_contains($envContent, 'VAPID_PRIVATE_KEY=')) {
            $envContent = preg_replace('/^VAPID_PRIVATE_KEY=.*$/m', "VAPID_PRIVATE_KEY={$privateKey}", $envContent);
        } else {
            $envContent .= "\nVAPID_PRIVATE_KEY={$privateKey}";
        }

        // Update or append VAPID_SUBJECT
        if (!str_contains($envContent, 'VAPID_SUBJECT=')) {
            $envContent .= "\nVAPID_SUBJECT=mailto:admin@studyhub.local";
        }

        // Append WA config if not exists
        if (!str_contains($envContent, 'WA_GATEWAY_PROVIDER=')) {
            $envContent .= "\n\n# WhatsApp Gateway Configuration\nWA_GATEWAY_PROVIDER=log\nWA_API_TOKEN=\nWA_API_URL=https://api.fonnte.com/send\n";
        }

        file_put_contents($envPath, $envContent);

        $this->info('VAPID keys successfully generated and written to .env!');
        $this->line("Public Key:  {$publicKey}");
        $this->line("Private Key: {$privateKey}");

        return self::SUCCESS;
    }
}
