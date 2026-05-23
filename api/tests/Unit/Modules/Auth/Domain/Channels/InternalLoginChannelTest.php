<?php

namespace Tests\Unit\Modules\Auth\Domain\Channels;

use App\Models\User;
use App\Modules\Auth\Domain\Channels\InternalLoginChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InternalLoginChannelTest extends TestCase
{
    use RefreshDatabase;

    private InternalLoginChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = new InternalLoginChannel;
    }

    public function test_resolves_user_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);

        $result = $this->channel->resolveUser([
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->assertNotNull($result);
        $this->assertTrue($user->is($result));
    }

    public function test_returns_null_when_password_is_invalid(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);

        $result = $this->channel->resolveUser([
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertNull($result);
    }

    public function test_returns_null_when_user_does_not_exist(): void
    {
        $result = $this->channel->resolveUser([
            'email' => 'missing@example.com',
            'password' => 'secret',
        ]);

        $this->assertNull($result);
    }

    public function test_returns_null_when_email_or_password_is_missing(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);

        $this->assertNull($this->channel->resolveUser(['password' => 'secret']));
        $this->assertNull($this->channel->resolveUser(['email' => 'admin@example.com']));
    }
}
