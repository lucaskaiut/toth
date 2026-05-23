<?php

namespace Tests\Unit\Modules\Auth\Domain\Services;

use App\Models\User;
use App\Modules\Auth\Domain\Contracts\RegisterChannel;
use App\Modules\Auth\Domain\DTOs\LoginResult;
use App\Modules\Auth\Domain\Exceptions\UnsupportedRegisterChannelException;
use App\Modules\Auth\Domain\Services\RegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RegisterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_login_result_with_token_when_channel_creates_user(): void
    {
        $user = User::factory()->create();
        $channel = $this->mockChannelReturning($user);

        $service = new RegisterService([
            'internal' => $channel,
        ]);

        $result = $service->register('internal', [
            'company_name' => 'Acme Inc',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->assertInstanceOf(LoginResult::class, $result);
        $this->assertTrue($user->is($result->user));
        $this->assertNotEmpty($result->token);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_returns_null_when_channel_returns_null(): void
    {
        $channel = $this->mockChannelReturning(null);

        $service = new RegisterService([
            'internal' => $channel,
        ]);

        $result = $service->register('internal', [
            'company_name' => 'Acme Inc',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->assertNull($result);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_throws_when_channel_is_not_registered(): void
    {
        $service = new RegisterService([]);

        $this->expectException(UnsupportedRegisterChannelException::class);

        $service->register('google', ['token' => 'abc']);
    }

    public function test_passes_data_payload_to_channel_unchanged(): void
    {
        $payload = [
            'company_name' => 'Acme Inc',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ];

        /** @var RegisterChannel&MockInterface $channel */
        $channel = Mockery::mock(RegisterChannel::class);
        $channel->shouldReceive('createUser')
            ->once()
            ->with($payload)
            ->andReturn(null);

        $service = new RegisterService(['internal' => $channel]);

        $this->assertNull($service->register('internal', $payload));
    }

    private function mockChannelReturning(?User $user): RegisterChannel&MockInterface
    {
        /** @var RegisterChannel&MockInterface $channel */
        $channel = Mockery::mock(RegisterChannel::class);
        $channel->shouldReceive('createUser')->once()->andReturn($user);

        return $channel;
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
