<?php

namespace Tests\Unit\Modules\Auth\Domain\Services;

use App\Models\User;
use App\Modules\Auth\Domain\Contracts\LoginChannel;
use App\Modules\Auth\Domain\DTOs\LoginResult;
use App\Modules\Auth\Domain\Exceptions\UnsupportedLoginChannelException;
use App\Modules\Auth\Domain\Services\LoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LoginServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_login_result_with_token_when_channel_resolves_user(): void
    {
        $user = User::factory()->create();
        $channel = $this->mockChannelReturning($user);

        $service = new LoginService([
            'internal' => $channel,
        ]);

        $result = $service->login('internal', ['email' => 'admin@example.com', 'password' => 'secret']);

        $this->assertInstanceOf(LoginResult::class, $result);
        $this->assertTrue($user->is($result->user));
        $this->assertNotEmpty($result->token);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_returns_null_when_channel_returns_null(): void
    {
        $channel = $this->mockChannelReturning(null);

        $service = new LoginService([
            'internal' => $channel,
        ]);

        $result = $service->login('internal', ['email' => 'admin@example.com', 'password' => 'wrong']);

        $this->assertNull($result);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_throws_when_channel_is_not_registered(): void
    {
        $service = new LoginService([]);

        $this->expectException(UnsupportedLoginChannelException::class);

        $service->login('google', ['token' => 'abc']);
    }

    public function test_passes_data_payload_to_channel_unchanged(): void
    {
        $payload = ['email' => 'admin@example.com', 'password' => 'secret'];

        /** @var LoginChannel&MockInterface $channel */
        $channel = Mockery::mock(LoginChannel::class);
        $channel->shouldReceive('resolveUser')
            ->once()
            ->with($payload)
            ->andReturn(null);

        $service = new LoginService(['internal' => $channel]);

        $this->assertNull($service->login('internal', $payload));
    }

    private function mockChannelReturning(?User $user): LoginChannel&MockInterface
    {
        /** @var LoginChannel&MockInterface $channel */
        $channel = Mockery::mock(LoginChannel::class);
        $channel->shouldReceive('resolveUser')->once()->andReturn($user);

        return $channel;
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
