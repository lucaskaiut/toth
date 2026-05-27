<?php

namespace Tests\Unit\Modules\Auth\Domain\Channels;

use App\Models\User;
use App\Modules\Auth\Domain\Channels\InternalRegisterChannel;
use App\Modules\Company\Domain\Enums\CompanyStatus;
use App\Modules\Company\Domain\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InternalRegisterChannelTest extends TestCase
{
    use RefreshDatabase;

    private InternalRegisterChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.api_key' => 'test-global-key',
            'whatsapp.base_url' => 'http://evolution.test',
        ]);

        Http::fake([
            'http://evolution.test/instance/create' => Http::response([], 201),
        ]);

        $this->channel = app(InternalRegisterChannel::class);
    }

    public function test_creates_company_and_user_via_relationship(): void
    {
        $result = $this->channel->createUser([
            'company_name' => 'Acme Inc',
            'whatsapp' => '5511999887766',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $this->assertNotNull($result);
        $this->assertInstanceOf(User::class, $result);
        $this->assertDatabaseHas('companies', [
            'name' => 'Acme Inc',
            'whatsapp' => '5511999887766',
            'status' => CompanyStatus::PendingWhatsappConnection->value,
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'company_id' => $result->company_id,
        ]);
        $this->assertTrue($result->company->is(Company::query()->first()));
        $this->assertDatabaseCount('pipeline_stages', 4);
    }

    public function test_returns_null_when_email_already_exists(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $result = $this->channel->createUser([
            'company_name' => 'Acme Inc',
            'whatsapp' => '5511999887766',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $this->assertNull($result);
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_returns_null_when_required_fields_are_missing(): void
    {
        $this->assertNull($this->channel->createUser([
            'company_name' => 'Acme Inc',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]));

        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('users', 0);
    }
}
