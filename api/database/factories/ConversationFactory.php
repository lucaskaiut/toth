<?php

namespace Database\Factories;

use App\Modules\Conversation\Domain\Enums\ConversationAttendanceStatus;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Lead\Domain\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'lead_id' => Lead::factory(),
            'summary' => fake()->optional()->sentence(),
            'attendance_status' => ConversationAttendanceStatus::AiEnabled,
        ];
    }
}
