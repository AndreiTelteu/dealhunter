<?php

namespace Tests\Feature\Crawlers;

use App\Services\Crawlers\CrawlerException;
use App\Services\Crawlers\Mcp\McpClient;
use App\Services\Crawlers\Mcp\McpSession;
use App\Services\Crawlers\Mcp\PlaywrightMcpClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlaywrightMcpClientTest extends TestCase
{
    public function test_it_maps_evaluate_to_the_current_function_only_schema(): void
    {
        Http::fake([
            'mcp.test/mcp' => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => '2025-06-18']], 200, ['Mcp-Session-Id' => 'session-1'])
                ->push([], 202)
                ->push(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['content' => [['type' => 'text', 'text' => "### Result\n\n[{\"title\":\"Laptop\"}]\n\n### Ran Playwright code\n\n```js\nawait page.evaluate('...');\n```"]]]]),
        ]);

        $session = new McpSession;
        $mcp = new PlaywrightMcpClient(new McpClient('http://mcp.test/mcp', '', ['retry_attempts' => 1], $session), $session);

        $this->assertSame([['title' => 'Laptop']], $mcp->evaluate('() => [{ title: "Laptop" }]'));

        Http::assertSent(function (Request $request): bool {
            if ($request['method'] !== 'tools/call' || $request['params']['name'] !== 'browser_evaluate') {
                return false;
            }

            $arguments = (array) $request['params']['arguments'];

            return $arguments['function'] === '() => [{ title: "Laptop" }]'
                && ! array_key_exists('args', $arguments);
        });
    }

    public function test_it_recovers_a_reset_session_and_maps_tool_errors(): void
    {
        Http::fake([
            'mcp.test/mcp' => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => '2025-06-18']], 200, ['Mcp-Session-Id' => 'session-1'])
                ->push([], 202)
                ->push('Session not found', 404)
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => '2025-06-18']], 200, ['Mcp-Session-Id' => 'session-2'])
                ->push([], 202)
                ->push(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['isError' => true, 'content' => [['type' => 'text', 'text' => 'Navigation denied']]]]),
        ]);

        $session = new McpSession;
        $mcp = new PlaywrightMcpClient(new McpClient('http://mcp.test/mcp', '', ['retry_attempts' => 1], $session), $session);

        $this->expectException(CrawlerException::class);
        $this->expectExceptionMessage('Navigation denied');

        $mcp->navigate('https://www.olx.ro/');

        Http::assertSentCount(6);
    }
}
