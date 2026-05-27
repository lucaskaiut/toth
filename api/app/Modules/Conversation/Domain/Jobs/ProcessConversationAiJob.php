<?php

namespace App\Modules\Conversation\Domain\Jobs;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Conversation\Domain\Services\ConversationAiProcessor;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessConversationAiJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $conversationId,
        public readonly int $companyId,
    ) {}

    public function handle(
        ConversationAiProcessor $processor,
        PipelineStageService $pipelineStageService,
    ): void {
        $company = Company::query()->find($this->companyId);

        if ($company === null) {
            return;
        }

        $pipelineStageService->seedForCompany($company);

        $conversation = Conversation::query()
            ->where('company_id', $this->companyId)
            ->with('lead.pipelineStage')
            ->find($this->conversationId);

        if ($conversation === null) {
            return;
        }

        $processor->process($conversation);
    }
}
