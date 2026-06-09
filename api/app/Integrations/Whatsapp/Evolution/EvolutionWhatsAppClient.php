<?php

namespace App\Integrations\Whatsapp\Evolution;

use App\Core\Whatsapp\Contracts\WhatsAppClient;
use App\Core\Whatsapp\DTOs\CreateWhatsAppInstanceData;
use App\Core\Whatsapp\DTOs\CreateWhatsAppInstanceResult;
use App\Core\Whatsapp\DTOs\IncomingWhatsAppMessage;
use App\Core\Whatsapp\DTOs\OutgoingWhatsAppMessage;
use App\Core\Whatsapp\DTOs\SendMessageResult;
use App\Core\Whatsapp\DTOs\WhatsAppConnectResult;
use App\Core\Whatsapp\DTOs\WhatsAppConnectionStateResult;
use App\Modules\IntegrationLog\Domain\Services\IntegrationLogService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class EvolutionWhatsAppClient implements WhatsAppClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout,
        private readonly EvolutionWebhookParser $webhookParser,
        private readonly IntegrationLogService $integrationLogService,
    ) {}

    public function send(OutgoingWhatsAppMessage $message): SendMessageResult
    {
        $number = $this->formatNumber($message->phone);
        $url = $this->buildUrl("/message/sendText/{$message->instanceName}");
        $headers = $this->requestHeaders();
        $body = [
            'number' => $number,
            'text' => $message->content,
        ];

        try {
            $this->integrationLogService->info(
                integration: 'whatsapp',
                action: 'send',
                message: 'Evolution sendText request',
                context: [
                    'url' => $url,
                    'headers' => $this->sanitizeHeadersForLog($headers),
                    'body' => $body,
                ],
            );

            $response = $this->http()
                ->post($url, $body);

            $this->integrationLogService->info(
                integration: 'whatsapp',
                action: 'send',
                message: 'Evolution sendText response',
                context: [
                    'url' => $url,
                    'status' => $response->status(),
                    'reason' => $response->reason(),
                    'response_body' => $response->json() ?? $response->body(),
                ],
            );

            if (! $response->successful()) {
                $this->integrationLogService->error(
                    integration: 'whatsapp',
                    action: 'send',
                    message: 'Falha ao enviar mensagem WhatsApp.',
                    context: [
                        'url' => $url,
                        'status' => $response->status(),
                        'body' => $response->json() ?? $response->body(),
                        'phone' => $message->phone,
                    ],
                );

                return new SendMessageResult(
                    success: false,
                    error: $response->json('message') ?? $response->body(),
                );
            }

            $externalId = $response->json('key.id')
                ?? $response->json('messageId')
                ?? null;

            return new SendMessageResult(
                success: true,
                externalId: is_string($externalId) ? $externalId : null,
            );
        } catch (\Throwable $exception) {
            $this->integrationLogService->error(
                integration: 'whatsapp',
                action: 'send',
                message: $exception->getMessage(),
                context: ['phone' => $message->phone],
            );

            return new SendMessageResult(
                success: false,
                error: $exception->getMessage(),
            );
        }
    }

    public function parseWebhook(array $payload): ?IncomingWhatsAppMessage
    {
        return $this->webhookParser->parse($payload);
    }

    public function createInstance(CreateWhatsAppInstanceData $data): CreateWhatsAppInstanceResult
    {
        $url = $this->buildUrl('/instance/create');
        $headers = $this->requestHeaders();
        $body = [
            'instanceName' => $data->instanceName,
            'number' => $this->formatNumber($data->number),
            'integration' => 'WHATSAPP-BAILEYS',
            'qrcode' => true,
            'rejectCall' => true,
            'alwaysOnline' => true,
            'readMessages' => true,
            'readStatus' => true,
            'syncFullHistory' => true,
            'webhook' => [
                'url' => $data->webhookUrl,
                'byEvents' => true,
                'base64' => true,
                'headers' => $data->webhookHeaders,
                'events' => $data->webhookEvents,
            ],
        ];

        $this->logCreateInstance('request', $url, $headers, $body);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($url, $body);

            $this->logCreateInstanceResponse($url, $response);

            if (! $response->successful()) {
                return new CreateWhatsAppInstanceResult(
                    success: false,
                    error: $response->json('message') ?? $response->body(),
                    raw: is_array($response->json()) ? $response->json() : null,
                );
            }

            return new CreateWhatsAppInstanceResult(
                success: true,
                raw: $response->json(),
            );
        } catch (\Throwable $exception) {
            $this->integrationLogService->error(
                integration: 'whatsapp',
                action: 'create_instance',
                message: 'Exceção ao criar instância Evolution.',
                context: [
                    'url' => $url,
                    'method' => 'POST',
                    'headers' => $this->sanitizeHeadersForLog($headers),
                    'body' => $this->sanitizeBodyForLog($body),
                    'exception' => $exception->getMessage(),
                    'exception_class' => $exception::class,
                ],
            );

            return new CreateWhatsAppInstanceResult(
                success: false,
                error: $exception->getMessage(),
            );
        }
    }

    public function connectInstance(string $instanceName): WhatsAppConnectResult
    {
        $url = $this->buildUrl("/instance/connect/{$instanceName}");

        try {
            $this->integrationLogService->info(
                integration: 'whatsapp',
                action: 'connect_instance',
                message: 'Evolution connect request',
                context: [
                    'url' => $url,
                    'headers' => $this->sanitizeHeadersForLog($this->requestHeaders()),
                ],
            );

            $response = $this->http()->get($url);

            $this->integrationLogService->info(
                integration: 'whatsapp',
                action: 'connect_instance',
                message: 'Evolution connect response',
                context: [
                    'url' => $url,
                    'status' => $response->status(),
                    'reason' => $response->reason(),
                    'response_body' => $response->json() ?? $response->body(),
                ],
            );

            if (! $response->successful()) {
                $this->logError('connect_instance', $response->status(), $response->json() ?? $response->body());

                return new WhatsAppConnectResult(
                    success: false,
                    error: $response->json('message') ?? $response->body(),
                );
            }

            $payload = $response->json() ?? [];
            $connection = is_array($payload['qrcode'] ?? null) ? $payload['qrcode'] : $payload;

            return new WhatsAppConnectResult(
                success: true,
                pairingCode: is_string($connection['pairingCode'] ?? $payload['pairingCode'] ?? null)
                    ? ($connection['pairingCode'] ?? $payload['pairingCode'])
                    : null,
                code: is_string($connection['code'] ?? $payload['code'] ?? null)
                    ? ($connection['code'] ?? $payload['code'])
                    : null,
                base64: is_string($connection['base64'] ?? $payload['base64'] ?? null)
                    ? ($connection['base64'] ?? $payload['base64'])
                    : null,
            );
        } catch (\Throwable $exception) {
            $this->logError('connect_instance', 0, $exception->getMessage());

            return new WhatsAppConnectResult(
                success: false,
                error: $exception->getMessage(),
            );
        }
    }

    public function getConnectionState(string $instanceName): WhatsAppConnectionStateResult
    {
        $url = $this->buildUrl("/instance/connectionState/{$instanceName}");

        try {
            $this->integrationLogService->info(
                integration: 'whatsapp',
                action: 'connection_state',
                message: 'Evolution connectionState request',
                context: [
                    'url' => $url,
                    'headers' => $this->sanitizeHeadersForLog($this->requestHeaders()),
                ],
            );

            $response = $this->http()->get($url);

            $this->integrationLogService->info(
                integration: 'whatsapp',
                action: 'connection_state',
                message: 'Evolution connectionState response',
                context: [
                    'url' => $url,
                    'status' => $response->status(),
                    'reason' => $response->reason(),
                    'response_body' => $response->json() ?? $response->body(),
                ],
            );

            if (! $response->successful()) {
                $this->logError('connection_state', $response->status(), $response->json() ?? $response->body());

                return new WhatsAppConnectionStateResult(
                    success: false,
                    error: $response->json('message') ?? $response->body(),
                );
            }

            $state = $response->json('instance.state')
                ?? $response->json('state')
                ?? $response->json('connectionStatus');

            return new WhatsAppConnectionStateResult(
                success: true,
                state: is_string($state) ? $state : null,
            );
        } catch (\Throwable $exception) {
            $this->logError('connection_state', 0, $exception->getMessage());

            return new WhatsAppConnectionStateResult(
                success: false,
                error: $exception->getMessage(),
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function requestHeaders(): array
    {
        return [
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout($this->timeout)->withHeaders($this->requestHeaders());
    }

    private function buildUrl(string $path): string
    {
        return rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $body
     */
    private function logCreateInstance(string $phase, string $url, array $headers, array $body): void
    {
        $this->integrationLogService->info(
            integration: 'whatsapp',
            action: 'create_instance',
            message: "Evolution create instance — {$phase}",
            context: [
                'phase' => $phase,
                'method' => 'POST',
                'url' => $url,
                'base_url_config' => $this->baseUrl,
                'timeout_seconds' => $this->timeout,
                'headers' => $this->sanitizeHeadersForLog($headers),
                'body' => $this->sanitizeBodyForLog($body),
            ],
        );
    }

    private function logCreateInstanceResponse(string $url, Response $response): void
    {
        $context = [
            'phase' => 'response',
            'method' => 'POST',
            'url' => $url,
            'status' => $response->status(),
            'reason' => $response->reason(),
            'response_headers' => $response->headers(),
            'response_body' => $response->json() ?? $response->body(),
        ];

        if ($response->successful()) {
            $this->integrationLogService->info(
                integration: 'whatsapp',
                action: 'create_instance',
                message: 'Evolution create instance — response OK',
                context: $context,
            );

            return;
        }

        $this->integrationLogService->error(
            integration: 'whatsapp',
            action: 'create_instance',
            message: 'Evolution create instance — response com erro',
            context: $context,
        );
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function sanitizeHeadersForLog(array $headers): array
    {
        $sanitized = $headers;

        if (isset($sanitized['apikey'])) {
            $sanitized['apikey'] = $this->maskSecret($sanitized['apikey']);
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function sanitizeBodyForLog(array $body): array
    {
        $sanitized = $body;

        if (
            isset($sanitized['webhook']['headers']['authorization'])
            && is_string($sanitized['webhook']['headers']['authorization'])
        ) {
            $sanitized['webhook']['headers']['authorization'] = $this->maskSecret(
                $sanitized['webhook']['headers']['authorization'],
            );
        }

        return $sanitized;
    }

    private function maskSecret(string $value): string
    {
        if ($value === '') {
            return '(vazio)';
        }

        if (strlen($value) <= 8) {
            return '***';
        }

        return substr($value, 0, 4).'…'.substr($value, -4).' ('.strlen($value).' chars)';
    }

    private function formatNumber(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }

    /**
     * @param  array<string, mixed>|string|null  $body
     */
    private function logError(string $action, int $status, array|string|null $body): void
    {
        $this->integrationLogService->error(
            integration: 'whatsapp',
            action: $action,
            message: 'Falha na integração Evolution.',
            context: [
                'status' => $status,
                'body' => $body,
            ],
        );
    }
}
