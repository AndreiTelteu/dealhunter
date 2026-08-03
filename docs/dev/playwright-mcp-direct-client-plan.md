# Migration Plan: Direct Microsoft Playwright MCP Client

> Status: **Planned — not yet implemented.**
> Scope: Migrate `OlxCrawlerService` from the obsolete REST `/playwright/{action}` calls to a direct Microsoft Playwright MCP **Streamable HTTP** client at `/mcp`, using JSON-RPC 2.0 over SSE.
> Constraint: Structured extraction must use `browser_evaluate`-based JavaScript evaluation — **no element-handle / snapshot-ref model** for data extraction.

---

## 1. Background & Problem Statement

### 1.1 Current (Obsolete) Transport

`OlxCrawlerService::mcpRequest()` (`app/Services/Crawlers/OlxCrawlerService.php:598-622`) issues plain HTTP POST requests to `{endpoint}/playwright/{action}` with a Bearer token. The `{action}` path segment is one of the custom verbs the old REST gateway exposed:

| Old REST action    | Used at (file:line)                                            |
|--------------------|----------------------------------------------------------------|
| `navigate`         | `OlxCrawlerService.php:268`, `:628`                            |
| `wait`             | `OlxCrawlerService.php:665`                                    |
| `fill`             | `OlxCrawlerService.php:275`                                    |
| `click`            | `OlxCrawlerService.php:280`, `:459`, `:681`                    |
| `query`            | `OlxCrawlerService.php:430`, `:649`                            |
| `queryAll`         | `OlxCrawlerService.php:303`                                    |
| `queryInElement`   | `OlxCrawlerService.php:485`, `:509`                            |
| `queryAllInElement`| `OlxCrawlerService.php:526`, `:533`                            |
| `getAttribute`     | `OlxCrawlerService.php:447`                                    |
| `getElementAttribute` | `OlxCrawlerService.php:541`                                 |

These verbs are **not** standard MCP tools. They were a bespoke REST facade that the upstream Playwright MCP server no longer exposes. The health check in `SystemHealthService::checkMcpConnection()` (`app/Services/SystemHealthService.php:79-121`) also pings `{endpoint}/health`, which is not part of the MCP protocol.

### 1.2 Target Transport: Microsoft Playwright MCP (Streamable HTTP)

The official `@playwright/mcp` server (Microsoft) speaks the **MCP Streamable HTTP** transport:

- **Endpoint**: one complete MCP URL, conventionally `http://host:3000/mcp`. `MCP_PLAYWRIGHT_ENDPOINT` is this complete URL — it must be set to `http://192.168.0.115:8931/mcp` in production, not `/sse`, and implementation must never append a second `/mcp` path segment.
- **Protocol**: JSON-RPC 2.0 over HTTP POST. Responses may be:
  - a single `application/json` body, or
  - an `text/event-stream` (SSE) body containing one or more `data: {json-rpc}` events, terminated by a final event.
- **Session lifecycle**: the first `initialize` request causes the server to return a `Mcp-Session-Id` response header. That header must be echoed as `Mcp-Session-Id` on every subsequent request. The server may also respond with `4xx` including `403` (token) or `400` (malformed request) / `406` (Not Acceptable) / `409` / `429` (rate limited / session limit).
- **Tools**: the server exposes MCP tools discoverable via `tools/list`. The canonical Playwright MCP tool names are prefixed with `browser_`:
  - `browser_navigate`, `browser_navigate_back`
  - `browser_click`, `browser_hover`, `browser_type`, `browser_fill_form`, `browser_select_option`, `browser_press_key`
  - `browser_snapshot` (accessibility tree with element `ref`s — used for interactive actions, **not** for extraction)
  - `browser_evaluate` (run arbitrary JS in the page; returns structured JSON — **this is the extraction vehicle**)
  - `browser_wait_for` (wait for text to appear/disappear or a fixed time)
  - `browser_tabs` (list/new/close/select)
  - `browser_take_screenshot`, `browser_close`
  - `browser_console_messages`, `browser_network_requests`

### 1.3 Why `browser_evaluate` for Extraction (No Element Handles)

The current code passes raw element arrays between calls (`queryInElement`, `queryAllInElement`, `getElementAttribute`). The MCP Playwright server's element-handle model is the **snapshot/ref** model: `browser_snapshot` returns refs, and `browser_click`/`browser_type` consume refs. Refs are ephemeral (invalidated on navigation or DOM mutation) and require chatty round-trips per element.

For crawling structured listings, a single `browser_evaluate` call that runs a self-contained JavaScript extractor in the page and returns a JSON array of listing objects is:

- **fewer round-trips** (one evaluate per page instead of N per listing),
- **immune to ref invalidation**,
- **deterministic** (the JS owns the selectors and fallback logic),
- **consistent** with the existing `OlxSelectors` constants by injecting them into the evaluate script.

Interactive actions that genuinely need the DOM (search input fill, search submit click, pagination click) will still use `browser_snapshot` + `browser_click`/`browser_type` via refs, because those are user-like interactions. **Data extraction** uses `browser_evaluate`.

---

## 2. Architecture

### 2.1 New Files

```
app/Services/Crawlers/
├── Mcp/
│   ├── McpClient.php                  Streamable HTTP transport + JSON-RPC/SSE parser
│   ├── McpSession.php                 Session lifecycle state container
│   ├── PlaywrightMcpClient.php        High-level Playwright-MCP tool wrapper
│   └── StreamableHttpResponse.php     Value object for parsed SSE/JSON response
├── OlxCrawlerService.php              (modified — swaps mcpRequest for PlaywrightMcpClient)
└── ... (existing support classes unchanged)
```

```
config/crawler.php                    (modified — add mcp_path, timeouts, session options)
```

```
tests/Feature/Crawlers/
├── McpClientSseParsingTest.php
├── McpSessionLifecycleTest.php
├── PlaywrightMcpClientToolMappingTest.php
├── OlxCrawlerServiceEvaluateExtractionTest.php
└── CrawlerHealthCheckMcpTest.php
```

> The project currently has no `tests/` directory, `phpunit.xml`, or installed PHPUnit/Pest executable. Implementation adds the standard Laravel PHPUnit bootstrap/configuration plus `phpunit/phpunit` as `require-dev`, then creates these PHPUnit-compatible tests. This is a development-only test dependency; runtime production dependencies remain unchanged.

### 2.2 Class Responsibilities

#### `McpClient` (transport)

Low-level HTTP client for Streamable HTTP JSON-RPC. Knows nothing about Playwright tool semantics.

- **Constructor**: `(string $endpoint, string $token, array $options)` where options include `timeout`, `connect_timeout`, `retry_attempts`, `retry_delay_ms`.
- **`initialize(): array`** — sends `initialize` JSON-RPC (`{jsonrpc:"2.0", id:1, method:"initialize", params:{protocolVersion:"2025-06-18", capabilities:{}, clientInfo:{name:"DealHunter", version:"x"}}}`). Captures `Mcp-Session-Id` from the response header. Stores it in the injected `McpSession`. Sends `notifications/initialized` as a follow-up notification (no response expected).
- **`call(string $method, ?array $params = null, ?int $id = null): mixed`** — sends a JSON-RPC request. Parses the response (single JSON or SSE stream). Returns the `result` field or throws on `error`.
- **`notify(string $method, array $params = []): void`** — sends a JSON-RPC notification (no `id`, no response expected).
- **`send(array $payload): StreamableHttpResponse`** — raw HTTP send + parse. Sets `Accept: application/json, text/event-stream`, `Content-Type: application/json`, `Mcp-Session-Id` (when active), and `User-Agent`. Sends `Authorization: Bearer …` **only when** `MCP_PLAYWRIGHT_TOKEN` is non-empty; the currently probed Microsoft server accepts initialization without it, so authentication must be optional rather than assumed.
- **`closeSession(): void`** — sends an HTTP DELETE to the endpoint with the `Mcp-Session-Id` header to terminate the session per the Streamable HTTP spec.

#### `McpSession` (state container)

- Holds: `?string $sessionId`, `?string $protocolVersion`, `?array $serverInfo`, `?array $serverCapabilities`, `bool $initialized`, `?int $nextRequestId`.
- `McpClient` writes session state here; `PlaywrightMcpClient` reads `sessionId` to know whether to (re)initialize.

#### `StreamableHttpResponse` (parsed response)

Value object wrapping the parsed result of a Streamable HTTP response:
- `int $httpStatus`
- `?string $sessionId` (from `Mcp-Session-Id` header, if present)
- `array $jsonRpcMessages` — one or more parsed JSON-RPC messages (from single JSON or SSE `data:` lines).
- `bool $isSse` — whether the response was an event stream.

#### `PlaywrightMcpClient` (tool wrapper)

High-level wrapper that maps crawler intents to Playwright MCP tools. Lazily initializes the MCP session on first use. Methods:

| Method                          | MCP tool             | Notes                                                                    |
|---------------------------------|----------------------|--------------------------------------------------------------------------|
| `navigate(string $url): array`  | `browser_navigate`   | `{url}` — returns `snapshot` / `url`.                                    |
| `waitForText(string $text): void` | `browser_wait_for` | `{text}`; the actual server does **not** expose a per-call timeout argument. HTTP tool-call timeout is enforced client-side. |
| `waitForGone(string $text): void` | `browser_wait_for` | `{textGone}`; client-side HTTP timeout applies. |
| `waitForTime(int $seconds): void` | `browser_wait_for` | `{time: seconds}`. |
| `snapshot(): array`             | `browser_snapshot`   | Returns accessibility tree with refs — optional fallback for interactive clicks. |
| `click(string $target, ?string $description = null): array` | `browser_click` | `{target}` where target is a snapshot ref **or a unique CSS selector**; optional `element` is only human-readable permission context. |
| `type(string $target, string $text, bool $submit = false): array` | `browser_type` | `{target, text, submit}`; target may be a snapshot ref or unique CSS selector. |
| `pressKey(string $key): array`  | `browser_press_key`  | `{key}`.                                                                 |
| `evaluate(string $function): mixed` | `browser_evaluate` | `{function}` only. The current server schema has no `args`; selector data must be safely embedded as a JSON literal in the arrow-function source. Returns decoded structured result. |
| `takeScreenshot(...): array`    | `browser_take_screenshot` | (optional) for debugging.                                           |
| `close(): void`                 | `browser_close`      | Closes the page (not session).                                           |
| `healthCheck(): bool`           | (composite)          | Calls `initialize` if needed; returns `true` on success. See §5.        |

The `evaluate` method is the core extraction vehicle. The `function` parameter is a JavaScript arrow-function string and the current server accepts no separate `args` property. PHP must `json_encode` the selector map with safe JSON flags and interpolate that **literal** into a closed function expression, rather than concatenating raw selector strings. For structured extraction, the function returns a plain object/array which the MCP server serializes into tool-result text.

> **Verified `browser_evaluate` signature**: `params: { function: string }`. The current server's tool schema has no `args`; `function` is a string such as `"() => { return [...]; }"`. The server evaluates it and returns the value through the normal MCP `tools/call` content envelope.

#### `OlxCrawlerService` (modified)

- Constructor injects `PlaywrightMcpClient` instead of reading `Http::` directly.
- `mcpRequest()` is **removed**. All call sites are rewritten to use `$this->mcp->navigate()`, `$this->mcp->evaluate()`, `$this->mcp->snapshot()` + `click()`, etc.
- The old "element handle" methods (`extractWithFallback`, `hasSelector`, `extractImageUrls`, `extractListingFromElement`) are replaced by a single `evaluate`-based extractor per page (see §4).
- `throttleRequest()`, `assertCrawlingPermitted()`, `retryWithBackoff()` are preserved unchanged — they wrap the new client calls.
- The client is session-scoped: `crawlHuntedDeal()` / `extractListings()` calls `$this->mcp->ensureInitialized()` at the start and `$this->mcp->closeSession()` in a `finally` block.

### 2.3 Dependency Strategy

The transport adds no production dependency. However, the repository currently has neither `vendor/bin/phpunit` nor `vendor/bin/pest`, no `tests/` directory, and no `phpunit.xml`. To make the proposed regression tests executable, implementation adds `phpunit/phpunit` as a **require-dev** dependency and Laravel's standard test bootstrap/configuration. Guzzle remains Laravel's existing transitive dependency and is used directly only if the Laravel HTTP facade cannot expose the required stream behavior.

- `Illuminate\Support\Facades\Http` (already in use) may be used for ordinary request construction; use Guzzle's PSR-7 stream handling for line-buffered SSE parsing when required.
- `json_decode` parses JSON-RPC messages. No JSON-RPC library is required; the protocol surface is small and explicit error mapping is needed.

---

## 3. MCP Session Lifecycle

### 3.1 Session Initialization (lazy, on first tool call)

```
[PlaywrightMcpClient]
  if (!session.initialized)
    McpClient.initialize()
      → POST /mcp  {initialize request}
      ← 200 OK, Mcp-Session-Id: <uuid>, body: {result:{protocolVersion, serverInfo, capabilities}}
    McpClient.notify("notifications/initialized")
      → POST /mcp  {notification, no id}
      ← 202 Accepted (or 200)
    session.initialized = true
```

### 3.2 Tool Call (per request)

```
[PlaywrightMcpClient]
  McpClient.call("tools/call", {name: "browser_navigate", arguments: {url}})
    → POST /mcp  {tools/call request}, headers include Mcp-Session-Id
    ← 200 OK
      Content-Type: application/json         → single JSON-RPC response
      Content-Type: text/event-stream        → SSE with data: events, parse until final
    parse result.content (array of content blocks) or error
```

The MCP `tools/call` result has shape `{result: {content: [{type:"text", text:"..."}], isError: bool}}` or `{result: {content: [{type:"image"...}]}}`. For `browser_evaluate`, the `content[0].text` contains the JSON-encoded return value; the wrapper `json_decode`s it.

### 3.3 Session Termination

- On `closeSession()`: `DELETE /mcp` with `Mcp-Session-Id` header → `200 OK`.
- The `OlxCrawlerService` calls `closeSession()` in a `finally` block at the end of each crawl operation (per hunted deal or per `extractListings` call).
- If the session is dropped unexpectedly (server returns `404`/`400` indicating unknown session), the wrapper re-initializes transparently **once** per crawl (to avoid silent reconnect loops).

### 3.4 Error States from the Transport

| HTTP status | Meaning                                           | Action                                              |
|-------------|---------------------------------------------------|-----------------------------------------------------|
| 200         | OK — parse body (JSON or SSE)                     | Parse and return result.                            |
| 202         | Accepted (notification)                           | No body expected; return success.                   |
| 400         | Bad request (malformed JSON-RPC)                  | Throw `McpProtocolException`.                       |
| 403         | Auth failure / token invalid                      | Throw `CrawlerException::mcpConnectionFailed`.      |
| 404         | Session not found (expired/invalid session ID)   | Mark session uninitialized; throw to trigger retry. |
| 406         | Not Acceptable (wrong Accept header)              | Throw `McpProtocolException` (config error).       |
| 409         | Conflict (session already initialized)            | Re-initialize session state.                        |
| 429         | Rate limited / session limit                       | Backoff and retry (see §7).                         |
| 5xx         | Server error                                      | Retry with backoff (see §7).                        |

JSON-RPC error objects (`{error:{code, message, data}}`) are parsed and mapped:
- `-32601` Method not found → `McpProtocolException` (tool name wrong).
- `-32602` Invalid params → `McpProtocolException` (arguments wrong).
- `-32603` Internal error → retry with backoff.
- `-32000` and below (server-defined) → map to `CrawlerException` with the message.

---

## 4. `browser_evaluate`-Based Structured Extraction (No Element Handles)

### 4.1 Principle

All data extraction is performed by a single `browser_evaluate` call per page. The JavaScript function is constructed in PHP, using the `OlxSelectors` constants for selectors, and returns a JSON-serializable array of listing objects. No element handles, refs, or snapshots are used for extraction — only for interactive actions (search, pagination).

### 4.2 Results-Page Extractor (`extractFromResultsPage`)

The current implementation (`OlxCrawlerService.php:297-344`) does:
1. `queryAll` for listing cards → returns element array.
2. Per element: `queryInElement` for title, url, price, location; `queryAllInElement` for images; `hasSelector` for flags. → ~6–8 MCP round-trips per listing.

The new implementation does:
1. One `browser_evaluate` with a function that:
   - Tries `OlxSelectors::LISTING_ITEM` then `LISTING_ITEM_FALLBACK` via `document.querySelectorAll`.
   - For each card, extracts: title (from `LISTING_TITLE` / `LISTING_TITLE_FALLBACK`), href URL (`LISTING_URL` / `LISTING_URL_FALLBACK`), price text (`LISTING_PRICE` / `LISTING_PRICE_FALLBACK`), location/date text (`LISTING_LOCATION` / `LISTING_LOCATION_FALLBACK`), image `src` values (`LISTING_IMAGE` / `LISTING_IMAGE_FALLBACK`), and boolean flags for promoted/urgent/negotiable (presence of `LISTING_PROMOTED` / `LISTING_URGENT` / `LISTING_NEGOTIABLE`).
   - Returns `[{url, title, price_raw, location, posted_at, image_urls, is_promoted, is_urgent, is_negotiable, metadata:{extraction_index, extraction_timestamp}}, ...]`.

The selector map is JSON-encoded in PHP and embedded as a literal inside the closed `browser_evaluate` function; the actual tool schema does not support an `args` parameter, so selectors must not be sent in a separate argument field.

**Function shape** (the `function` string passed to `browser_evaluate`):

```js
() => {
  const selectors = /* JSON-encoded selector-map literal injected by PHP */;
  const pick = (root, list) => {
    for (const sel of list) {
      const el = root.querySelector(sel);
      if (el) return el;
    }
    return null;
  };
  const text = (el, attr = 'textContent') => el ? (el[attr] || el.textContent || '').trim() : '';
  const all = (root, list) => {
    for (const sel of list) {
      const els = root.querySelectorAll(sel);
      if (els.length) return [...els];
    }
    return [];
  };
  const has = (root, sel) => !!root.querySelector(sel);

  const cardLists = [selectors.listing_item, selectors.listing_item_fallback];
  let cards = [];
  for (const sel of cardLists) {
    cards = document.querySelectorAll(sel);
    if (cards.length) break;
  }
  if (!cards.length) return [];

  return [...cards].map((card, index) => {
    const titleEl = pick(card, [selectors.listing_title, selectors.listing_title_fallback]);
    const urlEl = pick(card, [selectors.listing_url, selectors.listing_url_fallback]);
    const priceEl = pick(card, [selectors.listing_price, selectors.listing_price_fallback]);
    const locEl = pick(card, [selectors.listing_location, selectors.listing_location_fallback]);
    const imgEls = all(card, [selectors.listing_image, selectors.listing_image_fallback]);

    return {
      title: text(titleEl),
      url: urlEl ? (urlEl.href || urlEl.getAttribute('href') || '') : '',
      price_raw: text(priceEl),
      location: text(locEl),
      image_urls: imgEls.map(img => img.src || img.getAttribute('src') || '').filter(Boolean),
      is_promoted: has(card, selectors.listing_promoted),
      is_urgent: has(card, selectors.listing_urgent),
      is_negotiable: has(card, selectors.listing_negotiable),
      metadata: { extraction_index: index, extraction_timestamp: new Date().toISOString() },
    };
  });
}
```

PHP side builds the `args` object from `OlxSelectors` constants:
```php
'selectors' => [
    'listing_item' => OlxSelectors::LISTING_ITEM,
    'listing_item_fallback' => OlxSelectors::LISTING_ITEM_FALLBACK,
    'listing_title' => OlxSelectors::LISTING_TITLE,
    'listing_title_fallback' => OlxSelectors::LISTING_TITLE_FALLBACK,
    // ... all selectors needed by the function
]
```

The result of `$this->mcp->evaluate($function)` is the decoded array — identical in shape to what `extractListingFromElement` returned. `parseListingData()` and `processImageUrls()` are reused unchanged.

### 4.3 Detail-Page Extractor (`enrichListingsFromDetailPages`)

The current detail enrichment (`OlxCrawlerService.php:624-643`) navigates to each listing URL and calls `extractPageAttribute` for description, seller name, seller URL, and posted date — 4 round-trips per listing.

The new implementation uses one `browser_evaluate` per detail page:

```js
() => {
  const selectors = /* JSON-encoded selector-map literal injected by PHP */;
  const pick = (list, attr = 'textContent') => {
    for (const sel of list) {
      const el = document.querySelector(sel);
      if (el) {
        const val = attr === 'href' ? el.href : (el.textContent || '').trim();
        if (val) return val;
      }
    }
    return null;
  };
  return {
    description: pick([selectors.detail_description, selectors.detail_description_fallback]),
    seller_name: pick([selectors.detail_seller, selectors.detail_seller_fallback]),
    seller_url: pick([selectors.detail_seller_url, selectors.detail_seller_url_fallback], 'href'),
    posted_at: pick([selectors.detail_posted_date, selectors.detail_posted_date_fallback]),
  };
}
```

One navigate + one evaluate per listing (was: one navigate + 4 queries). `normalizePostedAt()` is applied in PHP after extraction, unchanged.

### 4.4 Interactive Actions (search, pagination) — Refs via Snapshot

Actions that simulate user behavior still use the snapshot/ref model:

- **Search**: `navigate(olx.ro)` → `snapshot()` → find the search input ref → `type(ref, searchTerm)` → `snapshot()` → find submit button ref → `click(ref)`. Then `waitForText` / `evaluate` to confirm results loaded.
  - Alternatively, `navigate` directly to the OLX search URL `https://www.olx.ro/ro/oferta/q-{encoded-search-term}/` to skip the form interaction entirely. This is the **preferred** approach: fewer MCP calls, no ref dependency. Fallback to form interaction if the URL scheme changes.
- **Pagination**: `snapshot()` → find `pagination-forward` ref → check disabled (via `evaluate` on the ref's `disabled` attribute, or via snapshot flag) → `click(ref)` → wait for results.

`waitForAnySelector` and `clickAnySelector` helper methods are replaced by:
- `waitForSelectorViaEvaluate(array $selectors, int $timeoutMs)` — runs an evaluate function that polls `document.querySelector` until found or timeout. Returns the matched selector string (for logging). This replaces the old `wait` action.
- `clickAnySelector` → `snapshot()` + ref lookup by selector text, then `click(ref)`. Or, simpler: a single `evaluate` that does `document.querySelector(sel)?.click()` for each fallback selector until one succeeds. This is acceptable for pagination buttons (no ref needed for a programmatic click).

> **Decision**: For clicks, prefer `evaluate` with `el.click()` when the action is deterministic (no need for real user-like events). Reserve `browser_click` with refs for cases where OLX has JS event listeners that require trusted events (rare). Start with `evaluate`-based clicks; fall back to `browser_click` if a click is observed to not take effect.

---

## 5. Health Check

### 5.1 `SystemHealthService::checkMcpConnection()` (modify)

Currently (`SystemHealthService.php:79-121`) does `Http::get($endpoint.'/health')`. Replace with a real MCP protocol check:

1. Instantiate `PlaywrightMcpClient` from config.
2. Call `healthCheck()`:
   - `initialize()` (JSON-RPC `initialize`) → if success, session is alive.
   - Optionally call `tools/list` and verify `browser_navigate` is present → confirms the server is the Playwright MCP (not some other MCP server).
   - `closeSession()`.
3. Map outcomes:
   - `initialize` OK + `browser_navigate` present → `healthy`.
   - `initialize` OK but `browser_navigate` missing → `warning` ("MCP server is not Playwright").
   - `initialize` fails (403/timeout) → `critical` ("MCP auth/connection failed: {error}").
   - Response time > 2000ms → `warning` ("MCP initialization is slow").

### 5.2 Config-Endpoint Verification

Treat `config('crawler.mcp_playwright_endpoint')` as the complete MCP URL. During health check, reject/warn if it is empty or its parsed path is not exactly `/mcp`; do **not** add a separate `mcp_playwright_path`, because a current endpoint that already ends in `/mcp` would otherwise become `/mcp/mcp`. Deployment changes `.env` from the current `/sse` value to `MCP_PLAYWRIGHT_ENDPOINT=http://192.168.0.115:8931/mcp`.

---

## 6. JSON-RPC / SSE Parser

### 6.1 JSON-RPC Request Envelope

All requests: `{"jsonrpc":"2.0","id":<int>,"method":<string>,"params":<object>}`.
Notifications (no response): `{"jsonrpc":"2.0","method":<string>,"params":<object>}` (no `id`).

### 6.2 Single-JSON Response

If `Content-Type: application/json`, the body is a single JSON-RPC response:
`{"jsonrpc":"2.0","id":<int>,"result":<object>}` or `{"jsonrpc":"2.0","id":<int>,"error":{"code":<int>,"message":<string>,"data":<mixed>}}`.

### 6.3 SSE Response

If `Content-Type: text/event-stream`, the body is a stream of SSE events. Each event is one or more `data:` lines followed by a blank line. The parser:

1. Reads the body stream line by line (using Guzzle's stream resource or `Psr\Http\Message\StreamInterface`).
2. Buffers `data:` lines per event. Multiple `data:` lines in one event are concatenated with `\n`.
3. On a blank line, the buffered `data` is `json_decode`d into a JSON-RPC message. Messages include:
   - `{"jsonrpc":"2.0","id":<int>,"result":...}` — the final response (id matches the request id).
   - `{"jsonrpc":"2.0","method":"notifications/progress","params":{...}}` — progress notifications (log and discard).
   - `{"jsonrpc":"2.0","method":"$/log","params":{...}}` — log notifications (log and discard).
4. Parsing stops when a message with a matching `id` is received (the final response). Earlier notifications are processed but not returned.

The `StreamableHttpResponse` collects all messages; `McpClient::call` finds the one with the matching `id` and returns its `result` (or throws on `error`).

### 6.4 Parser Edge Cases

- **No `data:` prefix**: lines without `data:` are ignored (comments, `event:`, `id:` lines per the SSE spec — though MCP doesn't use `id:`/`retry:`).
- **Partial lines**: if the stream ends mid-event, the partial buffer is discarded and a warning is logged.
- **Empty response body** with 200 status: treat as protocol error (throw `McpProtocolException`).
- **Non-UTF8 / malformed JSON in a `data:` line**: log the raw line, skip the event, continue parsing.

---

## 7. Retries & Error Handling

### 7.1 Transport-Level Retries (in `McpClient`)

Retries apply to the HTTP transport, **not** to MCP-level errors:

| Condition                              | Retry? | Max | Backoff         |
|----------------------------------------|--------|-----|-----------------|
| Connection timeout / network error     | Yes    | 3   | exponential 1s→2s→4s |
| HTTP 429                               | Yes    | 3   | `Retry-After` header or exponential |
| HTTP 5xx                               | Yes    | 3   | exponential 1s→2s→4s |
| HTTP 404 (session expired)             | No*    | —   | —               |
| HTTP 403 (auth)                        | No     | —   | —               |
| HTTP 400 / 406 (protocol/config error) | No     | —   | —               |
| JSON-RPC error -32603 (internal)       | Yes    | 2   | exponential     |
| JSON-RPC error -32601/-32602 (bad call)| No     | —   | —               |

\* HTTP 404 triggers a **session re-initialization** (one attempt), then a single retry of the original call. If that also fails, throw.

### 7.2 Crawler-Level Retries (in `OlxCrawlerService`)

The existing `retryWithBackoff()` in `BaseService` (`app/Services/BaseService.php:105-132`) is preserved. It wraps:
- `navigateToSearch()` — already wrapped, max 3 attempts, 2s base delay.
- `extractFromResultsPage()` — already wrapped, max 2 attempts, 1s base delay.

These continue to work because the new `PlaywrightMcpClient` methods throw the same `CrawlerException` types on failure. The wrapper catches `\Throwable` and retries.

### 7.3 New Exception Mapping

Add `McpProtocolException extends CrawlerException` for JSON-RPC protocol violations (bad method, bad params, parse errors). Keep `CrawlerException` for transport and tool-execution failures. Both surface to `BaseService::executeWithErrorHandling` which wraps them in `ServiceException` for the crawl operation.

### 7.4 Session Recovery

If a `tools/call` returns `404` or a JSON-RPC error indicating the session is invalid:
1. `PlaywrightMcpClient` marks the session as uninitialized.
2. Calls `ensureInitialized()` to re-establish the session.
3. Retries the original `tools/call` **once**.
4. If it fails again, throws `CrawlerException::mcpConnectionFailed`.

This is bounded to one recovery per call to avoid infinite loops.

### 7.5 Timeout Configuration

- MCP session initialize: 10s (configurable via `crawler.mcp_init_timeout_ms`).
- Individual tool calls: 30s (configurable via `crawler.timeout_ms`, already exists).
- SSE stream read: 30s per message (configurable).
- Health check: 10s total.

---

## 8. Configuration Changes (`config/crawler.php`)

> Not modified in this plan file — listed for reference. Keep `mcp_playwright_endpoint` as the full `/mcp` URL and add only transport tuning keys:

```php
'mcp_init_timeout_ms' => env('MCP_INIT_TIMEOUT_MS', 10000),
'mcp_sse_read_timeout_ms' => env('MCP_SSE_READ_TIMEOUT_MS', 30000),
'mcp_session_auto_recover' => env('MCP_SESSION_AUTO_RECOVER', true),
'mcp_protocol_version' => env('MCP_PROTOCOL_VERSION', '2025-06-18'),
```

`.env.example` gains those tuning keys and documents `MCP_PLAYWRIGHT_ENDPOINT=http://host:port/mcp`; it must not introduce a separate `MCP_PLAYWRIGHT_PATH`.

---

## 9. `OlxCrawlerService` Migration Map

| Old method / call site                          | New approach                                                         |
|-------------------------------------------------|----------------------------------------------------------------------|
| `mcpRequest('navigate', ['url'=>...])`          | `$this->mcp->navigate($url)`                                         |
| `mcpRequest('wait', ['selector'=>..., 'timeout'])` | `$this->waitForSelectorViaEvaluate([...], $timeout)` (evaluate-based) |
| `mcpRequest('fill', ['selector'=>..., 'value'])` | `$this->mcp->snapshot()` → find ref → `$this->mcp->type(ref, value)` OR direct URL navigation |
| `mcpRequest('click', ['selector'=>...])`        | `$this->mcp->evaluate(js that does el.click())` OR snapshot+click    |
| `mcpRequest('query'/'queryAll', ...)`           | Replaced by `browser_evaluate` extractor (see §4.2)                   |
| `mcpRequest('queryInElement'/'queryAllInElement', ...)` | Removed — extraction is evaluate-based, no element handles   |
| `mcpRequest('getAttribute'/'getElementAttribute', ...)` | Removed — attributes read inside the evaluate function       |
| `extractFromResultsPage()`                      | One `browser_evaluate` returning array of listing objects           |
| `extractListingFromElement()``                 | Removed — logic moves into the evaluate JS function                  |
| `extractWithFallback()`                         | Removed — fallback logic is in the evaluate JS function              |
| `hasSelector()`                                 | Removed — flag checks are in the evaluate JS function                 |
| `extractImageUrls()`                            | Removed — image extraction is in the evaluate JS function             |
| `extractPageAttribute()` (detail page)         | One `browser_evaluate` returning `{description, seller_name, seller_url, posted_at}` |
| `waitForAnySelector()`                          | `waitForSelectorViaEvaluate()` — polls `document.querySelector`     |
| `clickAnySelector()`                            | `evaluate`-based `el.click()` with fallback selectors                |
| `enrichListingsFromDetailPages()`               | Navigate + one evaluate per listing (unchanged loop structure)        |
| `navigateToSearch()`                            | Direct URL nav to `https://www.olx.ro/ro/oferta/q-{term}/` preferred; fallback to snapshot+type+click |
| `handlePagination()`                            | `evaluate` to check `disabled` + `el.click()`; or snapshot+click     |

Preserved unchanged:
- `crawlHuntedDeal()`, `performCrawl()`, `extractListings()` (flow control).
- `parseListingData()` (data→`ParsedListing` mapping).
- `processImageUrls()`, `normalizePostedAt()`.
- `throttleRequest()`, `assertCrawlingPermitted()`.
- `CrawlResult`, `ParsedListing`, `OlxSelectors`, `CrawlerException`.
- `TestCrawlerCommand`, `CrawlDealsCommand` (they consume `OlxCrawlerService` unchanged API).
- `CrawlerServiceProvider` (updated to also bind `PlaywrightMcpClient` as a singleton).

---

## 10. Test Plan

> The project has no `tests/` directory today. Implementation introduces PHPUnit via `php artisan test` and a `phpunit.xml`. Tests are **feature tests** (not unit) because they exercise the HTTP layer; they mock the MCP HTTP responses.

### 10.1 `McpClientSseParsingTest`

- Feed a canned SSE body (multi-event, with `notifications/progress` before the final result) and assert `McpClient::call` returns the correct `result`.
- Feed a single-JSON response and assert the same.
- Feed a malformed `data:` line and assert it's skipped without failing.
- Feed a response with `error` and assert `McpProtocolException` is thrown with the right code/message.

### 10.2 `McpSessionLifecycleTest`

- Assert `initialize` sends the right JSON-RPC envelope and captures `Mcp-Session-Id`.
- Assert subsequent calls include the session header.
- Assert `closeSession` sends DELETE.
- Assert session recovery on 404 (re-init + retry once).

### 10.3 `PlaywrightMcpClientToolMappingTest`

- Assert `navigate` calls `tools/call` with `{name:"browser_navigate", arguments:{url}}`.
- Assert `evaluate` calls `tools/call` with `{name:"browser_evaluate", arguments:{function, args}}`.
- Assert `snapshot` calls `browser_snapshot`.
- Assert `click`/`type` use the ref from a prior snapshot.
- Mock the HTTP layer; assert the JSON-RPC payloads.

### 10.4 `OlxCrawlerServiceEvaluateExtractionTest`

- Mock `PlaywrightMcpClient::evaluate` to return a canned listing array.
- Assert `extractListings()` returns `ParsedListing` objects with correct fields.
- Assert `enrichListingsFromDetailPages()` merges detail fields.
- Assert `normalizePostedAt()` handles "azi 14:30", "ieri 09:00", and ISO dates.
- Assert pagination loop stops when evaluate returns empty or `disabled`.

### 10.5 `CrawlerHealthCheckMcpTest`

- Mock `PlaywrightMcpClient::healthCheck()` returning true/false.
- Assert `SystemHealthService::checkMcpConnection()` maps to healthy/critical.
- Assert the `browser_navigate` presence check produces the "not Playwright" warning.

### 10.6 Manual Validation (update `manual-testing-guide.md`)

- `php artisan system:health-check` → verify `mcp` component is `healthy`.
- `php artisan crawler:test "laptop" --pages=1 --dry-run` → unchanged.
- `php artisan crawler:test "laptop" --pages=1` (with `CRAWLER_ENABLED=true`, terms acknowledged, in window) → verify listings appear.
- `php artisan deals:crawl --hunted-deal=1` → verify ingestion.

---

## 11. Deploy Validation

### 11.1 Pre-Deploy

1. **Config check**: resolved `crawler.mcp_playwright_endpoint` is exactly the complete `/mcp` URL (not `/sse`, and not `/mcp/mcp`).
2. **Health check**: `php artisan system:health-check` reports `mcp` = `healthy` (initialize + tools/list + browser_navigate present).
3. **Dry run**: `php artisan crawler:test "laptop" --pages=1 --dry-run` shows the resolved MCP URL and config.
4. **Lint**: `vendor/bin/pint --dirty` passes.
5. **Tests**: `php artisan test` passes (all new tests green).

### 11.2 Post-Deploy (live)

1. Set `CRAWLER_ENABLED=true`, `CRAWLER_TERMS_ACKNOWLEDGED=true`, ensure in `CRAWLER_ALLOWED_WINDOWS`.
2. `php artisan crawler:test "laptop" --pages=1` → exits 0, shows sample listings.
3. `php artisan deals:crawl --hunted-deal=1` → exits 0, deals/snapshots created.
4. Verify the dated crawler channel log (`storage/logs/crawler-YYYY-MM-DD.log`) records the crawl result without MCP protocol failures.
5. Verify no `mcpRequest` / `/playwright/` references remain: `grep -r 'playwright/' app/` returns nothing (except this plan doc and the notes doc).
6. `php artisan system:health-check` → `mcp` healthy, `crawler` healthy.

### 11.3 Rollback

If the new client fails in production:
- The old REST code is removed, so rollback = revert the deploy (git). No feature flag is added because the old transport is fundamentally broken (server no longer serves `/playwright/*`). A feature flag for "crawl enabled" already exists (`CRAWLER_ENABLED`).

---

## 12. Implementation Order (suggested)

1. `McpSession` + `StreamableHttpResponse` value objects.
2. `McpClient` with JSON-RPC envelope construction + SSE parser + session header handling.
3. `PlaywrightMcpClient` with `navigate`, `evaluate`, `snapshot`, `click`, `type`, `waitFor*`, `close`, `healthCheck`.
4. `McpClientSseParsingTest` + `McpSessionLifecycleTest` (against mocked HTTP).
5. Refactor `OlxCrawlerService`: swap `mcpRequest` for `PlaywrightMcpClient`, rewrite extractors as evaluate functions.
6. `OlxCrawlerServiceEvaluateExtractionTest` + `PlaywrightMcpClientToolMappingTest`.
7. Update `SystemHealthService::checkMcpConnection` + `CrawlerHealthCheckMcpTest`.
8. Config additions + `.env.example` update.
9. `CrawlerServiceProvider` binding for `PlaywrightMcpClient`.
10. Pint + test suite green.
11. Update `docs/dev/playwright-mcp-notes.md` and `docs/dev/manual-testing-guide.md` to reflect the new transport.
12. Deploy validation per §11.

---

## 13. Out of Scope

- Changing `OlxSelectors` constants (selectors are reused as-is, injected into evaluate functions).
- Changing `ParsedListing`, `CrawlResult`, `CrawlerException` data shapes.
- Changing `CrawlDealsCommand` / `TestCrawlerCommand` signatures.
- Adding production Composer dependencies; the PHPUnit `require-dev` addition described in §§2.3 and 10 is explicitly in scope.
- Implementing `robots.txt` parsing (noted in existing notes as intentionally absent).
- Browser `browser_take_screenshot` / `browser_console_messages` for debugging (optional future addition).

---

## 14. Risks & Mitigations

| Risk                                                  | Mitigation                                                                      |
|-------------------------------------------------------|---------------------------------------------------------------------------------|
| SSE streaming with Laravel `Http` facade is clunky     | Drop to Guzzle client directly (already a transitive dependency).              |
| `browser_evaluate` blocked by OLX CSP                | MCP runs in Node/Playwright context with page-add-init-script; CSP doesn't block `evaluate`. If it does, fall back to `browser_snapshot` + ref-based extraction (slower but functional). |
| Session drops mid-crawl (long crawl, server timeout)  | Session recovery (§7.4) re-initializes once; crawl continues.                   |
| OLX changes search URL scheme                          | Fallback to form-interaction path (snapshot+type+click).                        |
| `browser_evaluate` return value too large (many listings) | Paginate the evaluate (extract in chunks by `index` range) or rely on the page cap (`max_listings_per_run`). |
| MCP server version drift (tool names change)           | `tools/list` discovery at health-check time; log available tools.              |

---

*End of plan.*
