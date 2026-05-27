<?php

namespace Database\Factories;

use App\Modules\Company\Domain\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'whatsapp' => '5511999999999',
            'status' => \App\Modules\Company\Domain\Enums\CompanyStatus::Active,
        ];
    }

    public function pendingWhatsapp(): static
    {
        return $this->state(fn () => [
            'status' => \App\Modules\Company\Domain\Enums\CompanyStatus::PendingWhatsappConnection,
        ]);
    }
}
