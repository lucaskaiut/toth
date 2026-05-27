<?php

namespace App\Modules\Whatsapp\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversation\Domain\Services\IncomingMessageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly IncomingMessageProcessor $incomingMessageProcessor,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $expectedToken = (string) config('whatsapp.webhook_token');

        if ($expectedToken !== '') {
            $authorization = (string) $request->header('authorization', '');
            $valid = $authorization === $expectedToken
                || $authorization === "Bearer {$expectedToken}"
                || $authorization === 'Bearer '.$expectedToken;

            if (! $valid) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $this->incomingMessageProcessor->handleWebhook($request->all());

        return response()->json(['status' => 'ok']);
    }
}
