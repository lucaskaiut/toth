<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Modules\Conversation\Domain\Enums\ConversationAttendanceStatus;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Realtime\Events\ConversationUpdated;

class ConversationAttendanceService
{
    public function allowsAiProcessing(Conversation $conversation): bool
    {
        return $conversation->attendance_status->allowsAiProcessing();
    }

    public function transition(
        Conversation $conversation,
        ConversationAttendanceStatus $status,
    ): Conversation {
        $conversation->attendance_status = $status;
        $conversation->save();

        $fresh = $conversation->fresh(['lead.pipelineStage']);

        broadcast(new ConversationUpdated($conversation->company_id, $fresh))->toOthers();

        return $fresh;
    }

    public function handoffToHuman(Conversation $conversation): Conversation
    {
        if ($conversation->attendance_status === ConversationAttendanceStatus::Closed) {
            return $conversation;
        }

        if ($conversation->attendance_status === ConversationAttendanceStatus::HandoffToHuman) {
            return $conversation;
        }

        return $this->transition($conversation, ConversationAttendanceStatus::HandoffToHuman);
    }
}
