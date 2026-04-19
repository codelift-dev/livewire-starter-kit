<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuthLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $path = $this->authLogPath();
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    public function test_successful_login_is_written_to_auth_channel(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertFileExists($this->authLogPath());
        $this->assertStringContainsString('login_succeeded', file_get_contents($this->authLogPath()));
    }

    public function test_failed_login_is_written_to_auth_channel(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertFileExists($this->authLogPath());
        $this->assertStringContainsString('login_failed', file_get_contents($this->authLogPath()));
    }

    private function authLogPath(): string
    {
        return storage_path('logs/auth-'.Carbon::now()->format('Y-m-d').'.log');
    }
}
