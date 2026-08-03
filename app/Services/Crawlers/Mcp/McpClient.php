<?php

namespace App\Services\Crawlers\Mcp;

use App\Services\Crawlers\CrawlerException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use JsonException;

class McpClient
{
    /**
     * @param  array{timeout_ms?: int, init_timeout_ms?: int, connect_timeout_ms?: int, retry_attempts?: int, retry_delay_ms?: int, protocol_version?: string, user_agent?: string}  $options
     */
    public function __construct(
        private readonly string $endpoint,
        private readonly string $token,
        private readonly array $options,
        private readonly McpSession $session,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function initialize(): array
    {
        $result = $this->call('initialize', [
            'protocolVersion' => $this->options['protocol_version'] ?? '2025-06-18',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'DealHunter', 'version' => '1.0'],
        ], timeoutMs: (int) ($this->options['init_timeout_ms'] ?? $this->options['timeout_ms'] ?? 30000));

        if (! is_array($result) || ! $this->session->sessionId) {
            throw new McpProtocolException('MCP initialization did not return a session ID.');
        }

        $this->session->protocolVersion = $result['protocolVersion'] ?? null;
        $this->session->serverInfo = $result['serverInfo'] ?? null;
        $this->session->serverCapabilities = $result['capabilities'] ?? null;
        $this->notify('notifications/initialized');
        $this->session->initialized = true;

        return $result;
    }

    public function call(string $method, ?array $params = null, ?int $id = null, ?int $timeoutMs = null): mixed
    {
        $id ??= $this->session->nextRequestId++;
        $payload = ['jsonrpc' => '2.0', 'id' => $id, 'method' => $method];
        if ($params !== null) {
            $payload['params'] = $params;
        }

        $response = $this->send($payload, $timeoutMs);
        $message = collect($response->jsonRpcMessages)->first(fn (array $message): bool => ($message['id'] ?? null) === $id);

        if (! is_array($message)) {
            throw new McpProtocolException("MCP response did not contain a result for request {$id}.");
        }

        if (isset($message['error'])) {
            $error = $message['error'];
            throw new McpProtocolException($error['message'] ?? 'MCP request failed.', (int) ($error['code'] ?? 0));
        }

        if (! array_key_exists('result', $message)) {
            throw new McpProtocolException("MCP response for request {$id} has no result.");
        }

        return $message['result'];
    }

    public function notify(string $method, array $params = []): void
    {
        $payload = ['jsonrpc' => '2.0', 'method' => $method, 'params' => (object) $params];
        $this->send($payload);
    }

    public function closeSession(): void
    {
        if (! $this->session->sessionId) {
            return;
        }

        try {
            $this->request()->delete($this->endpoint);
        } finally {
            $this->session->reset();
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(array $payload, ?int $timeoutMs = null): StreamableHttpResponse
    {
        $attempt = 0;
        $maxAttempts = max(1, (int) ($this->options['retry_attempts'] ?? 3));

        while (true) {
            try {
                $response = $this->request($timeoutMs)->post($this->endpoint, $payload);
            } catch (ConnectionException $exception) {
                if (++$attempt >= $maxAttempts) {
                    throw CrawlerException::mcpConnectionFailed($this->endpoint, $exception->getMessage());
                }

                $this->sleepBeforeRetry($attempt);

                continue;
            }

            if (($response->status() === 429 || $response->serverError()) && ++$attempt < $maxAttempts) {
                $this->sleepBeforeRetry($attempt, $response->header('Retry-After'));

                continue;
            }

            if (! $response->successful() && $response->status() !== 202) {
                if ($response->status() === 404) {
                    $this->session->reset();
                }

                throw CrawlerException::mcpConnectionFailed($this->endpoint, "HTTP {$response->status()}: {$response->body()}");
            }

            $body = $response->body();
            if ($response->status() === 202 && $body === '') {
                return new StreamableHttpResponse(202, $response->header('Mcp-Session-Id'), [], false);
            }

            if ($body === '' && $response->status() !== 202) {
                throw new McpProtocolException('MCP returned an empty response body.');
            }

            $isSse = str_contains(strtolower((string) $response->header('Content-Type')), 'text/event-stream');
            $messages = $isSse ? $this->parseSse($body) : $this->parseJson($body);
            $sessionId = $response->header('Mcp-Session-Id');
            if ($sessionId) {
                $this->session->sessionId = $sessionId;
            }

            return new StreamableHttpResponse($response->status(), $sessionId, $messages, $isSse);
        }
    }

    private function request(?int $timeoutMs = null): PendingRequest
    {
        $headers = [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
            'User-Agent' => $this->options['user_agent'] ?? 'DealHunter MCP Client',
        ];

        if ($this->session->sessionId) {
            $headers['Mcp-Session-Id'] = $this->session->sessionId;
        }

        if ($this->token !== '') {
            $headers['Authorization'] = 'Bearer '.$this->token;
        }

        return Http::withHeaders($headers)
            ->timeout(max(1, (int) (($timeoutMs ?? $this->options['timeout_ms'] ?? 30000) / 1000)))
            ->connectTimeout(max(1, (int) (($this->options['connect_timeout_ms'] ?? 10000) / 1000)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseJson(string $body): array
    {
        try {
            $message = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new McpProtocolException('Invalid JSON response from MCP.', previous: $exception);
        }

        return is_array($message) ? [$message] : throw new McpProtocolException('MCP JSON response is not an object.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseSse(string $body): array
    {
        $messages = [];
        $eventData = [];

        foreach (preg_split('/\r\n|\r|\n/', $body) ?: [] as $line) {
            if ($line === '') {
                $this->appendSseEvent($messages, $eventData);
                $eventData = [];
            } elseif (str_starts_with($line, 'data:')) {
                $eventData[] = ltrim(substr($line, 5));
            }
        }

        $this->appendSseEvent($messages, $eventData);

        return $messages;
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, string>  $eventData
     */
    private function appendSseEvent(array &$messages, array $eventData): void
    {
        if ($eventData === []) {
            return;
        }

        try {
            $message = json_decode(implode("\n", $eventData), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        if (is_array($message)) {
            $messages[] = $message;
        }
    }

    private function sleepBeforeRetry(int $attempt, ?string $retryAfter = null): void
    {
        $delayMs = is_numeric($retryAfter) ? (int) $retryAfter * 1000 : (int) ($this->options['retry_delay_ms'] ?? 1000) * (2 ** ($attempt - 1));
        usleep($delayMs * 1000);
    }
}
