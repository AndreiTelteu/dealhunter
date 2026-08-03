<?php

namespace Tests\Feature\Crawlers;

use App\Services\Crawlers\Mcp\McpClient;
use App\Services\Crawlers\Mcp\McpProtocolException;
use App\Services\Crawlers\Mcp\McpSession;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class McpClientTest extends TestCase
{
    public function test_it_parses_sse_final_results_after_notifications(): void
    {
        Http::fake([
            'mcp.test/mcp' => Http::response("event: message\ndata: {\"jsonrpc\":\"2.0\",\"method\":\"notifications/progress\",\"params\":{}}\n\ndata: {\"jsonrpc\":\"2.0\",\"id\":1,\"result\":{\"ok\":true}}\n\n", 200, ['Content-Type' => 'text/event-stream']),
        ]);

        $client = $this->client();

        $this->assertSame(['ok' => true], $client->call('tools/list'));
    }

    public function test_it_throws_for_json_rpc_errors(): void
    {
        Http::fake(['mcp.test/mcp' => Http::response(['jsonrpc' => '2.0', 'id' => 1, 'error' => ['code' => -32602, 'message' => 'Invalid params']])]);

        $this->expectException(McpProtocolException::class);
        $this->expectExceptionMessage('Invalid params');
        $this->client()->call('tools/list');
    }

    public function test_it_sends_session_headers_and_closes_the_session(): void
    {
        Http::fake([
            'mcp.test/mcp' => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => '2025-06-18']], 200, ['Mcp-Session-Id' => 'session-1'])
                ->push([], 202)
                ->push(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['tools' => []]])
                ->push([], 200),
        ]);

        $client = $this->client();
        $client->initialize();
        $client->call('tools/list');
        $client->closeSession();

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST' && $request['method'] === 'tools/list' && $request->header('Mcp-Session-Id') === ['session-1'];
        });
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE' && $request->header('Mcp-Session-Id') === ['session-1']);
    }

    private function client(): McpClient
    {
        return new McpClient('http://mcp.test/mcp', '', ['timeout_ms' => 1000, 'connect_timeout_ms' => 1000, 'retry_attempts' => 1], new McpSession);
    }
}
