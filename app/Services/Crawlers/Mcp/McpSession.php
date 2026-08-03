<?php

namespace App\Services\Crawlers\Mcp;

class McpSession
{
    public ?string $sessionId = null;

    public ?string $protocolVersion = null;

    public ?array $serverInfo = null;

    public ?array $serverCapabilities = null;

    public bool $initialized = false;

    public int $nextRequestId = 1;

    public function reset(): void
    {
        $this->sessionId = null;
        $this->protocolVersion = null;
        $this->serverInfo = null;
        $this->serverCapabilities = null;
        $this->initialized = false;
        $this->nextRequestId = 1;
    }
}
