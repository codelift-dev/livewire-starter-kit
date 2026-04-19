<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

class AuthActivitySubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            Registered::class => 'handleRegistered',
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            PasswordReset::class => 'handlePasswordReset',
            TwoFactorAuthenticationEnabled::class => 'handleTwoFactorEnabled',
            TwoFactorAuthenticationDisabled::class => 'handleTwoFactorDisabled',
        ];
    }

    public function handleRegistered(Registered $event): void
    {
        $this->log('user_registered', [
            'user_id' => $event->user->getKey(),
            'email' => $event->user->email ?? null,
        ]);
    }

    public function handleLogin(Login $event): void
    {
        $this->log('login_succeeded', [
            'user_id' => $event->user->getKey(),
            'email' => $event->user->email ?? null,
            'remember' => $event->remember,
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        $this->log('logout', [
            'user_id' => $event->user?->getKey(),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        $this->log('login_failed', [
            'email' => $event->credentials['email'] ?? null,
            'user_id' => $event->user?->getKey(),
        ]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->log('password_reset', [
            'user_id' => $event->user->getKey(),
            'email' => $event->user->email ?? null,
        ]);
    }

    public function handleTwoFactorEnabled(TwoFactorAuthenticationEnabled $event): void
    {
        $this->log('two_factor_enabled', [
            'user_id' => $event->user->getKey(),
        ]);
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->log('two_factor_disabled', [
            'user_id' => $event->user->getKey(),
        ]);
    }

    private function log(string $event, array $context): void
    {
        Log::channel('auth')->info($event, array_merge($context, [
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]));
    }
}
