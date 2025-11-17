<?php

namespace Fperdomo\App\Http;

use Illuminate\Support\Facades\Http;

/**
 * HTTP URL Scanner
 *
 * Scans a collection of URLs and identifies invalid ones based on HTTP status codes.
 * A URL is considered invalid if it returns a status code >= 400 (client or server errors).
 *
 * @package Fperdomo\App\Http
 * @author  Fperdomo
 *
 * @example
 * ```php
 * $scanner = new Scanner([
 *     'https://example.com',
 *     'https://invalid-url.com',
 *     'https://another-site.com'
 * ]);
 *
 * $invalidUrls = $scanner->getInvalidUrls();
 * // Returns: [['url' => 'https://invalid-url.com', 'status' => 404]]
 * ```
 */
final readonly class Scanner
{

    /**
     * Create a new HTTP Scanner instance
     *
     * @param array<int, string> $urls An array of URLs to scan
     *
     * @example
     * ```php
     * $scanner = new Scanner([
     *     'https://example.com',
     *     'https://google.com'
     * ]);
     * ```
     */
    public function __construct(
        public array $urls
    )
    {
    }

    /**
     * Get all invalid URLs from the collection
     *
     * Iterates through all URLs and checks their HTTP status codes.
     * URLs returning status codes >= 400 are considered invalid.
     * If an exception occurs during the check, the URL is assigned a 500 status.
     *
     * @return array<int, array{url: string, status: int}> An array of invalid URLs with their HTTP status codes
     *
     * @example
     * ```php
     * $invalidUrls = $scanner->getInvalidUrls();
     * foreach ($invalidUrls as $result) {
     *     echo "URL: {$result['url']} - Status: {$result['status']}\n";
     * }
     * ```
     */
    public function getInvalidUrls(): array
    {
        $invalidUrls = [];
        foreach ($this->urls as $url) {
            try {
                $statusCode = $this->getStatusCodeForUrl($url);
            } catch (\Exception $e) {
                $statusCode = 500;
            }

            if ($statusCode >= 400) {
                $invalidUrls[] = [
                    'url' => $url,
                    'status' => $statusCode
                ];
            }
        }

        return $invalidUrls;
    }

    /**
     * Get the HTTP status code for a given URL
     *
     * Performs an HTTP GET request to the specified URL and returns its status code.
     *
     * @param string $url The remote URL to check
     *
     * @return int The HTTP status code (e.g., 200, 404, 500)
     *
     * @throws \Exception If the HTTP request fails
     *
     * @example
     * ```php
     * $statusCode = $scanner->getStatusCodeForUrl('https://example.com');
     * // Returns: 200
     * ```
     */
    protected function getStatusCodeForUrl(string $url): int
    {
        $httpResponse = Http::get($url);

        return $httpResponse->getStatusCode();
    }
}
