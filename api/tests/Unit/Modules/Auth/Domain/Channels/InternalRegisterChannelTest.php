<?php

namespace Tests\Unit\Modules\Auth\Domain\Channels;

use App\Models\User;
use App\Modules\Auth\Domain\Channels\InternalRegisterChannel;
use App\Modules\Company\Domain\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalRegisterChannelTest extends TestCase
{
    use RefreshDatabase;

    private InternalRegisterChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = new InternalRegisterChannel;
    }

    public function test_creates_company_and_user_via_relationship(): void
    {
        $result = $this->channel->createUser([
            'company_name' => 'Acme Inc',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->assertNotNull($result);
        $this->assertInstanceOf(User::class, $result);
        $this->assertDatabaseHas('companies', ['name' => 'Acme Inc']);
        $this->assertDatabaseHas('users', [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'company_id' => $result->company_id,
        ]);
        $this->assertTrue($result->company->is(Company::query()->first()));
    }

    public function test_returns_null_when_email_already_exists(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $result = $this->channel->createUser([
            'company_name' => 'Acme Inc',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->assertNull($result);
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_returns_null_when_required_fields_are_missing(): void
    {
        $this->assertNull($this->channel->createUser([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]));

        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('users', 0);
    }
}
