<?php

namespace App\Modules\Conversation\Domain\Jobs;

use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Conversation\Domain\Services\ConversationAiProcessor;
use App\Modules\Conversation\Domain\Services\ConversationAttendanceService;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class DebouncedProcessConversationAiJob implements ShouldQueue, ShouldBeUnique
{
    use FoundationQueueable;
    use InteractsWithQueue;

    public function __construct(
        public readonly int $conversationId,
        public readonly int $companyId,
    ) {}

    public function uniqueId(): string
    {
        return "{$this->companyId}:{$this->conversationId}";
    }

    public function uniqueFor(): int
    {
        // Tempo suficiente para cobrir re-agendamentos (debounce) e execução.
        return 60;
    }

    public function handle(
        ConversationAiProcessor $processor,
        PipelineStageService $pipelineStageService,
        ConversationAttendanceService $attendanceService,
    ): void {
        $debounceUntil = Cache::get($this->cacheKey());

        if ($debounceUntil instanceof \DateTimeInterface) {
            $debounceUntil = $debounceUntil->getTimestamp();
        }

        if (is_numeric($debounceUntil)) {
            $seconds = (int) $debounceUntil - now()->getTimestamp();

            if ($seconds > 0) {
                $this->release($seconds);

                return;
            }
        }

        $conversation = Conversation::query()
            ->where('company_id', $this->companyId)
            ->with('lead.pipelineStage')
            ->find($this->conversationId);

        if ($conversation === null) {
            Cache::forget($this->cacheKey());
            return;
        }

        if (! $attendanceService->allowsAiProcessing($conversation)) {
            Cache::forget($this->cacheKey());
            return;
        }

        // Garante estágios padrão (pode ser primeira mensagem da empresa).
        $company = \App\Modules\Company\Domain\Models\Company::query()->find($this->companyId);
        if ($company) {
            $pipelineStageService->seedForCompany($company);
        }

        $processor->process($conversation);

        Cache::forget($this->cacheKey());
    }

    private function cacheKey(): string
    {
        return "ai_debounce_until:{$this->companyId}:{$this->conversationId}";
    }
}

