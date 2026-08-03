<?php

namespace App\Services\Crawlers\Mcp;

use App\Services\Crawlers\CrawlerException;

class PlaywrightMcpClient
{
    public function __construct(
        private readonly McpClient $client,
        private readonly McpSession $session,
    ) {}

    /** @return array<string, mixed> */
    public function navigate(string $url): array
    {
        return $this->tool('browser_navigate', ['url' => $url]);
    }

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        return $this->tool('browser_snapshot');
    }

    /** @return array<string, mixed> */
    public function click(string $target, ?string $description = null): array
    {
        return $this->tool('browser_click', array_filter(['target' => $target, 'element' => $description]));
    }

    /** @return array<string, mixed> */
    public function type(string $target, string $text, bool $submit = false): array
    {
        return $this->tool('browser_type', ['target' => $target, 'text' => $text, 'submit' => $submit]);
    }

    public function waitForText(string $text): void
    {
        $this->tool('browser_wait_for', ['text' => $text]);
    }

    public function waitForGone(string $text): void
    {
        $this->tool('browser_wait_for', ['textGone' => $text]);
    }

    public function waitForTime(int $seconds): void
    {
        $this->tool('browser_wait_for', ['time' => $seconds]);
    }

    public function evaluate(string $function): mixed
    {
        $result = $this->tool('browser_evaluate', ['function' => $function]);
        $text = $result['content'][0]['text'] ?? null;

        if (! is_string($text)) {
            return $result;
        }

        return json_decode($text, true) ?? $text;
    }

    /** @return array<string, mixed> */
    public function tools(): array
    {
        $this->ensureInitialized();

        return $this->client->call('tools/list');
    }

    public function healthCheck(): bool
    {
        $tools = $this->tools()['tools'] ?? [];

        return collect($tools)->contains(fn (array $tool): bool => ($tool['name'] ?? null) === 'browser_navigate');
    }

    public function ensureInitialized(): void
    {
        if (! $this->session->initialized) {
            $this->client->initialize();
        }
    }

    public function closeSession(): void
    {
        $this->client->closeSession();
    }

    /** @return array<string, mixed> */
    private function tool(string $name, array $arguments = []): array
    {
        $this->ensureInitialized();

        try {
            $result = $this->client->call('tools/call', ['name' => $name, 'arguments' => (object) $arguments]);
        } catch (CrawlerException $exception) {
            if (! $this->session->initialized) {
                $this->ensureInitialized();
                $result = $this->client->call('tools/call', ['name' => $name, 'arguments' => (object) $arguments]);
            } else {
                throw $exception;
            }
        }

        if (! is_array($result) || ($result['isError'] ?? false)) {
            throw new CrawlerException($result['content'][0]['text'] ?? "MCP tool {$name} failed.");
        }

        return $result;
    }
}
