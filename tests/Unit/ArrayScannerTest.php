<?php

use Fperdomo\App\Url\ArrayScanner;
use Illuminate\Support\Facades\Http;

describe('Url\ArrayScanner', function () {
    test('can be created from an array of URLs', function () {
        $scanner = ArrayScanner::create([
            'https://example.com',
            'https://google.com',
        ]);

        expect($scanner)->toBeInstanceOf(ArrayScanner::class)
            ->and($scanner->urls)->toHaveCount(2);
    });

    test('can be instantiated directly', function () {
        $scanner = new ArrayScanner(['https://example.com']);

        expect($scanner)->toBeInstanceOf(ArrayScanner::class);
    });

    test('returns invalid URLs from array', function () {
        Http::fake([
            'https://example.com' => Http::response('', 200),
            'https://invalid-url.com' => Http::response('', 404),
        ]);

        $scanner = ArrayScanner::create([
            'https://example.com',
            'https://invalid-url.com',
        ]);

        $invalidUrls = $scanner->getInvalidUrls();

        expect($invalidUrls)->toHaveCount(1)
            ->and($invalidUrls[0]['url'])->toBe('https://invalid-url.com');
    });

    test('returns empty array when all URLs are valid', function () {
        Http::fake([
            '*' => Http::response('', 200),
        ]);

        $scanner = ArrayScanner::create([
            'https://example.com',
            'https://google.com',
        ]);

        expect($scanner->getInvalidUrls())->toBeEmpty();
    });

    test('handles empty array', function () {
        $scanner = ArrayScanner::create([]);

        expect($scanner->getInvalidUrls())->toBeEmpty();
    });

    test('processes multiple URLs correctly', function () {
        Http::fake([
            'https://valid1.com' => Http::response('', 200),
            'https://valid2.com' => Http::response('', 200),
            'https://invalid1.com' => Http::response('', 404),
            'https://invalid2.com' => Http::response('', 500),
        ]);

        $scanner = ArrayScanner::create([
            'https://valid1.com',
            'https://invalid1.com',
            'https://valid2.com',
            'https://invalid2.com',
        ]);

        $invalidUrls = $scanner->getInvalidUrls();

        expect($invalidUrls)->toHaveCount(2)
            ->and($invalidUrls[0]['url'])->toBe('https://invalid1.com')
            ->and($invalidUrls[1]['url'])->toBe('https://invalid2.com');
    });
});
