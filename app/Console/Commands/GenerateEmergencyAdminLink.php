<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class GenerateEmergencyAdminLink extends Command
{
    protected $signature = 'sentinel:admin-link {--email=admin@sentinel.grok} {--minutes=10}';

    protected $description = 'Generate a one-time emergency admin login link';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $minutes = max(1, (int) $this->option('minutes'));

        $user = User::query()
            ->where('email', $email)
            ->where('is_admin', true)
            ->first();

        if (! $user) {
            $this->error('Admin user not found for the given email.');

            return self::FAILURE;
        }

        $nonce = Str::random(64);

        Cache::put(
            sprintf('sentinel:emergency-admin-link:%d:%s', $user->id, $nonce),
            true,
            now()->addMinutes($minutes),
        );

        $url = URL::temporarySignedRoute(
            'emergency.admin.login',
            now()->addMinutes($minutes),
            [
                'user' => $user->id,
                'nonce' => $nonce,
            ],
        );

        $this->line('Emergency admin link generated.');
        $this->newLine();
        $this->line('Admin: '.$user->email);
        $this->line('Expires in: '.$minutes.' minute(s)');
        $this->newLine();
        $this->line($url);

        return self::SUCCESS;
    }
}