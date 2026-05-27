<?php

namespace Database\Factories;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Lead\Domain\Enums\DefaultPipelineStage;
use App\Modules\Lead\Domain\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PipelineStage>
 */
class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    public function definition(): array
    {
        $stage = DefaultPipelineStage::NovoLead;

        return [
            'company_id' => Company::factory(),
            'name' => $stage->label(),
            'slug' => $stage->value,
            'position' => $stage->position(),
        ];
    }
}
