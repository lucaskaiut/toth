<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Core\AI\Contracts\AiClient;
use App\Core\AI\DTOs\AiChatRequest;
use App\Core\Whatsapp\Contracts\WhatsAppClient;
use App\Core\Whatsapp\DTOs\OutgoingWhatsAppMessage;
use App\Modules\CompanyConfig\Domain\Services\CompanyAiConfigResolver;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;
use App\Modules\Conversation\Domain\Enums\MessageOrigin;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\ExternalIntegration\Domain\Services\ExternalToolService;
use App\Modules\IntegrationLog\Domain\Services\IntegrationLogService;
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
        private readonly CompanyAiConfigResolver $companyAiConfigResolver,
        private readonly ConversationAiToolRunner $conversationAiToolRunner,
        private readonly ExternalToolService $externalToolService,
        private readonly IntegrationLogService $integrationLogService,
        private readonly WhatsAppClient $whatsAppClient,
    ) {}

    public function process(Conversation $conversation): void
    {
        $conversation->loadMissing('lead');

        if (! $conversation->attendance_status->allowsAiProcessing()) {
            return;
        }

        $companyId = $conversation->company_id;
        $aiConfig = $this->companyAiConfigResolver->resolve($companyId);

        if (! $aiConfig->isConfigured()) {
            return;
        }

        $config = new CompanyConfigResolver($companyId);

        $messages = $this->contextBuilder->build($conversation);

        $hasExternalTools = $this->externalToolService->connectionForCompany($companyId) !== null;

        $chatRequest = new AiChatRequest(
            baseUrl: $aiConfig->baseUrl,
            model: $aiConfig->model,
            apiKey: $aiConfig->apiKey,
            messages: $messages,
            companyId: $companyId,
        );

        if ($hasExternalTools) {
            try {
                $aiResponse = $this->conversationAiToolRunner->run(
                    $companyId,
                    $aiConfig->baseUrl,
                    $aiConfig->model,
                    $aiConfig->apiKey,
                    $messages,
                );
            } catch (\Throwable $exception) {
                $this->integrationLogService->error(
                    integration: 'external:tools',
                    action: 'conversation_run',
                    message: $exception->getMessage(),
                    companyId: $companyId,
                );

                $aiResponse = $this->aiClient->chat($chatRequest);
            }
        } else {
            $aiResponse = $this->aiClient->chat($chatRequest);
        }

        DB::transaction(function () use ($conversation, $aiResponse, $companyId, $config) {
            $this->conversationService->updateSummary($conversation, $aiResponse->summary);

            $previousStageId = $conversation->lead->pipeline_stage_id;
            $lead = $this->leadService->moveToStageBySlugForAi($conversation->lead, $aiResponse->suggestedStage);
            $conversation->setRelation('lead', $lead);

            if ($aiResponse->shouldReply && $aiResponse->message !== '') {
                $aiMessage = $this->messageService->store(
                    $conversation,
                    MessageOrigin::Ai,
                    $aiResponse->message,
                );

                broadcast(new MessageCreated($companyId, $aiMessage))->toOthers();
                $this->sendWhatsApp($lead->phone, $aiResponse->message, $config);
            }

            broadcast(new \App\Modules\Realtime\Events\ConversationUpdated($companyId, $conversation->fresh(['lead.pipelineStage'])))->toOthers();

            if ($previousStageId !== $lead->pipeline_stage_id) {
                broadcast(new LeadStageChanged($companyId, $lead))->toOthers();
            }
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
