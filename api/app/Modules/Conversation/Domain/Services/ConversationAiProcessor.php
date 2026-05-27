<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Core\AI\Contracts\AiClient;
use App\Core\AI\DTOs\AiChatRequest;
use App\Core\Whatsapp\Contracts\WhatsAppClient;
use App\Core\Whatsapp\DTOs\OutgoingWhatsAppMessage;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;
use App\Modules\Conversation\Domain\Enums\MessageOrigin;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Lead\Domain\Services\LeadService;
use App\Modules\Realtime\Events\LeadStageChanged;
use App\Modules\Realtime\Events\MessageCreated;
use Illuminate\Support\Facades\DB;

class ConversationAiProcessor
{
    public function __construct(
        private readonly ConversationContextBuilder $contextBuilder,
        private readonly MessageService $messageService,
        private readonly ConversationService $conversationService,
        private readonly LeadService $leadService,
        private readonly AiClient $aiClient,
        private readonly WhatsAppClient $whatsAppClient,
    ) {}

    public function process(Conversation $conversation): void
    {
        $conversation->loadMissing('lead');

        if (! $conversation->attendance_status->allowsAiProcessing()) {
            return;
        }

        $companyId = $conversation->company_id;
        $config = new CompanyConfigResolver($companyId);

        $apiKey = (string) $config->get('ai.api_key', '');
        $model = (string) ($config->get('ai.model') ?? config('ai.default_model'));

        if ($apiKey === '') {
            return;
        }

        $messages = $this->contextBuilder->build($conversation);

        $aiResponse = $this->aiClient->chat(new AiChatRequest(
            model: $model,
            apiKey: $apiKey,
            messages: $messages,
        ));

        DB::transaction(function () use ($conversation, $aiResponse, $companyId, $config) {
            $aiMessage = $this->messageService->store(
                $conversation,
                MessageOrigin::Ai,
                $aiResponse->message,
            );

            $this->conversationService->updateSummary($conversation, $aiResponse->summary);

            $previousStageId = $conversation->lead->pipeline_stage_id;
            $lead = $this->leadService->moveToStageBySlug($conversation->lead, $aiResponse->suggestedStage);
            $conversation->setRelation('lead', $lead);

            broadcast(new MessageCreated($companyId, $aiMessage))->toOthers();
            broadcast(new \App\Modules\Realtime\Events\ConversationUpdated($companyId, $conversation->fresh(['lead.pipelineStage'])))->toOthers();

            if ($previousStageId !== $lead->pipeline_stage_id) {
                broadcast(new LeadStageChanged($companyId, $lead))->toOthers();
            }

            $this->sendWhatsApp($lead->phone, $aiResponse->message, $config);
        });
    }

    private function sendWhatsApp(string $phone, string $content, CompanyConfigResolver $config): void
    {
        $instanceName = (string) $config->get('evolution.instance_name', '');

        if ($instanceName === '') {
            return;
        }

        $this->whatsAppClient->send(new OutgoingWhatsAppMessage(
            phone: $phone,
            content: $content,
            instanceName: $instanceName,
        ));
    }
}
