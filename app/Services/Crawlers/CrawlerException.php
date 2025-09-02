<?php

namespace App\Services\Crawlers;

use App\Services\ServiceException;

/**
 * Exception class for crawler-specific errors
 * 
 * Extends the base ServiceException with crawler-specific context
 */
class CrawlerException extends ServiceException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = [],
        public readonly ?string $searchTerm = null,
        public readonly ?string $url = null,
        public readonly ?string $selector = null
    ) {
        $crawlerContext = array_merge($context, array_filter([
            'search_term' => $this->searchTerm,
            'url' => $this->url,
            'selector' => $this->selector
        ]));
        
        parent::__construct($message, $code, $previous, $crawlerContext);
    }
    
    /**
     * Create exception for MCP connection failure
     * 
     * @param string $endpoint
     * @param string $error
     * @param array $context
     * @return self
     */
    public static function mcpConnectionFailed(string $endpoint, string $error, array $context = []): self
    {
        return new self(
            message: "MCP connection failed: {$error}",
            context: array_merge($context, ['mcp_endpoint' => $endpoint])
        );
    }
    
    /**
     * Create exception for selector not found
     * 
     * @param string $selector
     * @param string $url
     * @param array $context
     * @return self
     */
    public static function selectorNotFound(string $selector, string $url, array $context = []): self
    {
        return new self(
            message: "Selector not found: {$selector}",
            context: $context,
            url: $url,
            selector: $selector
        );
    }
    
    /**
     * Create exception for navigation failure
     * 
     * @param string $url
     * @param string $error
     * @param array $context
     * @return self
     */
    public static function navigationFailed(string $url, string $error, array $context = []): self
    {
        return new self(
            message: "Navigation failed: {$error}",
            context: $context,
            url: $url
        );
    }
    
    /**
     * Create exception for extraction failure
     * 
     * @param string $searchTerm
     * @param string $error
     * @param array $context
     * @return self
     */
    public static function extractionFailed(string $searchTerm, string $error, array $context = []): self
    {
        return new self(
            message: "Extraction failed: {$error}",
            context: $context,
            searchTerm: $searchTerm
        );
    }
}