<?php

namespace App\Modules\Conversation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Conversation\Domain\Enums\ConversationAttendanceStatus;
use App\Modules\Conversation\Domain\Services\ConversationAttendanceService;
use App\Modules\Conversation\Domain\Services\ConversationService;
use App\Modules\Conversation\Domain\Services\OutgoingMessageService;
use App\Modules\Conversation\Http\Requests\SendMessageRequest;
use App\Modules\Conversation\Http\Requests\UpdateAttendanceStatusRequest;
use App\Modules\Conversation\Http\Resources\ConversationResource;
use App\Modules\Conversation\Http\Resources\MessageResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly OutgoingMessageService $outgoingMessageService,
        private readonly ConversationAttendanceService $attendanceService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return ConversationResource::collection($this->conversationService->all());
    }

    public function show(Conversation $conversation): ConversationResource
    {
        return new ConversationResource($this->conversationService->find($conversation->id));
    }

    public function messages(Conversation $conversation): AnonymousResourceCollection
    {
        $conversation = $this->conversationService->find($conversation->id);

        return MessageResource::collection(
            app(\App\Modules\Conversation\Domain\Services\MessageService::class)
                ->forConversation($conversation),
        );
    }

    public function sendMessage(SendMessageRequest $request, Conversation $conversation): MessageResource
    {
        $conversation = $this->conversationService->find($conversation->id);

        $message = $this->outgoingMessageService->sendManual(
            $conversation,
            $request->validated('content'),
            $request->user(),
        );

        return new MessageResource($message);
    }

    public function updateAttendanceStatus(
        UpdateAttendanceStatusRequest $request,
        Conversation $conversation,
    ): ConversationResource {
        $conversation = $this->conversationService->find($conversation->id);

        $status = ConversationAttendanceStatus::from($request->validated('attendance_status'));

        $updated = $this->attendanceService->transition($conversation, $status);

        return new ConversationResource($updated);
    }
}
