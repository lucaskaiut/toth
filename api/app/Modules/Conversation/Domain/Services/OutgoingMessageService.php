<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Core\Whatsapp\Contracts\WhatsAppClient;
use App\Core\Whatsapp\DTOs\OutgoingWhatsAppMessage;
use App\Models\User;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;
use App\Modules\Conversation\Domain\Enums\MessageOrigin;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Conversation\Domain\Models\Message;
use App\Modules\Realtime\Events\ConversationUpdated;
use App\Modules\Realtime\Events\MessageCreated;
use Illuminate\Support\Facades\DB;

class OutgoingMessageService
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly WhatsAppClient $whatsAppClient,
        private readonly ConversationAttendanceService $attendanceService,
    ) {}

    public function sendManual(Conversation $conversation, string $content, User $user): Message
    {
        return DB::transaction(function () use ($conversation, $content, $user) {
            $message = $this->messageService->store(
                $conversation,
                MessageOrigin::User,
                $content,
                $user,
            );

            broadcast(new MessageCreated($conversation->company_id, $message))->toOthers();
            $conversation = $this->attendanceService->handoffToHuman($conversation);

            $this->sendWhatsApp($conversation, $content);

            return $message;
        });
    }

    private function sendWhatsApp(Conversation $conversation, string $content): void
    {
        $config = new CompanyConfigResolver($conversation->company_id);
        $instanceName = (string) $config->get('evolution.instance_name', '');

        if ($instanceName === '') {
            return;
        }

        $conversation->loadMissing('lead');

        $this->whatsAppClient->send(new OutgoingWhatsAppMessage(
            phone: $conversation->lead->phone,
            content: $content,
            instanceName: $instanceName,
        ));
    }
}
