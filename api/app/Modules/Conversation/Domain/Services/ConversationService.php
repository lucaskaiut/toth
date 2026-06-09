<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Conversation\Domain\Enums\ConversationAttendanceStatus;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Lead\Domain\Models\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function __construct(
        private readonly CurrentCompany $currentCompany,
    ) {}

    /**
     * @return Collection<int, Conversation>
     */
    public function all(): Collection
    {
        return Conversation::query()
            ->where('company_id', $this->currentCompany->id())
            ->with(['lead.pipelineStage'])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function find(int $id): Conversation
    {
        return Conversation::query()
            ->where('company_id', $this->currentCompany->id())
            ->with(['lead.pipelineStage'])
            ->findOrFail($id);
    }

    public function findOrCreateForLead(Lead $lead): Conversation
    {
        return Conversation::query()->firstOrCreate(
            ['lead_id' => $lead->id],
            [
                'company_id' => $lead->company_id,
                'attendance_status' => ConversationAttendanceStatus::AiEnabled,
            ],
        );
    }

    public function findOrCreateForLeadId(int $leadId, int $companyId): Conversation
    {
        return Conversation::query()->firstOrCreate(
            ['lead_id' => $leadId],
            [
                'company_id' => $companyId,
                'attendance_status' => ConversationAttendanceStatus::AiEnabled,
            ],
        );
    }

    public function updateSummary(Conversation $conversation, string $summary): Conversation
    {
        $maxLength = (int) config('ai.summary_max_length', 800);

        if ($maxLength > 0 && mb_strlen($summary) > $maxLength) {
            $summary = mb_substr($summary, 0, $maxLength);
        }

        $conversation->summary = $summary;
        $conversation->save();

        return $conversation->fresh();
    }

    public function touch(Conversation $conversation): void
    {
        $conversation->touch();
    }
}
