<?php

namespace App\Services\Crawlers\Mcp;

class StreamableHttpResponse
{
    /**
     * @param  array<int, array<string, mixed>>  $jsonRpcMessages
     */
    public function __construct(
        public readonly int $httpStatus,
        public readonly ?string $sessionId,
        public readonly array $jsonRpcMessages,
        public readonly bool $isSse,
    ) {}
}
