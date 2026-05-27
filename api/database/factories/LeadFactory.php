<?php

namespace Database\Factories;

use App\Modules\Lead\Domain\Models\Lead;
use App\Modules\Lead\Domain\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'pipeline_stage_id' => 1,
            'name' => fake()->name(),
            'phone' => fake()->numerify('55119########'),
            'email' => fake()->optional()->safeEmail(),
            'company_name' => fake()->optional()->company(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forStage(PipelineStage $stage): static
    {
        return $this->state(fn () => [
            'company_id' => $stage->company_id,
            'pipeline_stage_id' => $stage->id,
        ]);
    }
}
