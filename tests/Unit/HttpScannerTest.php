<?php

use Fperdomo\App\Http\Scanner;
use Illuminate\Support\Facades\Http;

describe('Http\Scanner', function () {
    test('can be instantiated with an array of URLs', function () {
        $scanner = new Scanner(['https://example.com']);

        expect($scanner)->toBeInstanceOf(Scanner::class)
            ->and($scanner->urls)->toBe(['https://example.com']);
    });

    test('returns empty array when all URLs are valid', function () {
        Http::fake([
            'https://example.com' => Http::response('', 200),
            'https://google.com' => Http::response('', 200),
        ]);

        $scanner = new Scanner([
            'https://example.com',
            'https://google.com',
        ]);

        $invalidUrls = $scanner->getInvalidUrls();

        expect($invalidUrls)->toBeArray()
            ->toBeEmpty();
    });

    test('identifies URLs with 404 status as invalid', function () {
        Http::fake([
            'https://example.com' => Http::response('', 200),
            'https://invalid-url.com' => Http::response('Not Found', 404),
        ]);

        $scanner = new Scanner([
            'https://example.com',
            'https://invalid-url.com',
        ]);

        $invalidUrls = $scanner->getInvalidUrls();

        expect($invalidUrls)->toHaveCount(1)
            ->and($invalidUrls[0])->toMatchArray([
                'url' => 'https://invalid-url.com',
                'status' => 404,
            ]);
    });

    test('identifies URLs with 500 status as invalid', function () {
        Http::fake([
            'https://broken-server.com' => Http::response('Server Error', 500),
        ]);

        $scanner = new Scanner(['https://broken-server.com']);

        $invalidUrls = $scanner->getInvalidUrls();

        expect($invalidUrls)->toHaveCount(1)
            ->and($invalidUrls[0]['status'])->toBe(500);
    });

    test('handles multiple invalid URLs', function () {
        Http::fake([
            'https://example.com' => Http::response('', 200),
            'https://not-found.com' => Http::response('', 404),
            'https://server-error.com' => Http::response('', 503),
            'https://forbidden.com' => Http::response('', 403),
        ]);

        $scanner = new Scanner([
            'https://example.com',
            'https://not-found.com',
            'https://server-error.com',
            'https://forbidden.com',
        ]);

        $invalidUrls = $scanner->getInvalidUrls();

        expect($invalidUrls)->toHaveCount(3)
            ->and($invalidUrls[0]['url'])->toBe('https://not-found.com')
            ->and($invalidUrls[1]['url'])->toBe('https://server-error.com')
            ->and($invalidUrls[2]['url'])->toBe('https://forbidden.com');
    });

    test('handles exceptions by returning 500 status', function () {
        Http::fake([
            'https://exception-url.com' => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        $scanner = new Scanner(['https://exception-url.com']);

        $invalidUrls = $scanner->getInvalidUrls();

        expect($invalidUrls)->toHaveCount(1)
            ->and($invalidUrls[0])->toMatchArray([
                'url' => 'https://exception-url.com',
                'status' => 500,
            ]);
    });

    test('correctly categorizes client errors (4xx) as invalid', function () {
        Http::fake([
            'https://bad-request.com' => Http::response('', 400),
            'https://unauthorized.com' => Http::response('', 401),
            'https://forbidden.com' => Http::response('', 403),
            'https://not-found.com' => Http::response('', 404),
        ]);

        $scanner = new Scanner([
            'https://bad-request.com',
            'https://unauthorized.com',
            'https://forbidden.com',
            'https://not-found.com',
        ]);

        $invalidUrls = $scanner->getInvalidUrls();

        expect($invalidUrls)->toHaveCount(4);
    });

    test('returns URLs with their corresponding status codes', function () {
        Http::fake([
            'https://not-found.com' => Http::response('', 404),
            'https://server-error.com' => Http::response('', 500),
        ]);

        $scanner = new Scanner([
            'https://not-found.com',
            'https://server-error.com',
        ]);

        $invalidUrls = $scanner->getInvalidUrls();

        expect($invalidUrls[0])->toHaveKeys(['url', 'status'])
            ->and($invalidUrls[0]['status'])->toBe(404)
            ->and($invalidUrls[1]['status'])->toBe(500);
    });
});
