<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Core\Whatsapp\Contracts\WhatsAppClient;
use App\Core\Whatsapp\DTOs\IncomingWhatsAppMessage;
use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use App\Modules\Conversation\Domain\Enums\MessageOrigin;
use App\Modules\Conversation\Domain\Jobs\DebouncedProcessConversationAiJob;
use App\Modules\Conversation\Domain\Services\ConversationAttendanceService;
use App\Modules\Lead\Domain\Services\LeadService;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use App\Modules\Realtime\Events\MessageCreated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IncomingMessageProcessor
{
    public function __construct(
        private readonly WhatsAppClient $whatsAppClient,
        private readonly PipelineStageService $pipelineStageService,
        private readonly LeadService $leadService,
        private readonly ConversationService $conversationService,
        private readonly MessageService $messageService,
        private readonly ConversationAttendanceService $attendanceService,
    ) {}

    public function handleWebhook(array $payload): void
    {
        $incoming = $this->whatsAppClient->parseWebhook($payload);

        if ($incoming === null) {
            return;
        }

        $this->processIncoming($incoming);
    }

    public function processIncoming(IncomingWhatsAppMessage $incoming): void
    {
        $companyId = CompanyConfig::query()
            ->where('key', 'evolution.instance_name')
            ->where('value', $incoming->instanceName)
            ->value('company_id');

        if ($companyId === null) {
            return;
        }

        $this->pipelineStageService->seedForCompany(
            \App\Modules\Company\Domain\Models\Company::query()->findOrFail($companyId),
        );

        DB::transaction(function () use ($incoming, $companyId) {
            $lead = $this->leadService->findOrCreateByPhoneForCompany(
                $companyId,
                $incoming->phone,
                $incoming->senderName,
            );

            $conversation = $this->conversationService->findOrCreateForLeadId($lead->id, $companyId);

            $message = $this->messageService->store(
                $conversation,
                MessageOrigin::Customer,
                $incoming->content,
            );

            broadcast(new MessageCreated($companyId, $message))->toOthers();
            broadcast(new \App\Modules\Realtime\Events\ConversationUpdated(
                $companyId,
                $conversation->fresh(['lead.pipelineStage']),
            ))->toOthers();

            DB::afterCommit(function () use ($conversation, $companyId) {
                $conversation->refresh();

                if (! $this->attendanceService->allowsAiProcessing($conversation)) {
                    return;
                }

                // Debounce fixo: aguarda N segundos sem novas mensagens do cliente antes de acionar a IA.
                $delaySeconds = max(0, (int) config('ai.debounce_seconds', 10));

                Cache::put(
                    "ai_debounce_until:{$companyId}:{$conversation->id}",
                    now()->addSeconds($delaySeconds),
                    $delaySeconds + 60,
                );

                // Job único por conversa: se já existir um job pendente, ele será reaproveitado
                // e irá "release" até atingir o debounce_until mais recente.
                DebouncedProcessConversationAiJob::dispatch($conversation->id, $companyId)->delay($delaySeconds);
            });
        });
    }
}
