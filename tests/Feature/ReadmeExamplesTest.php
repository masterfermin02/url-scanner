<?php

use Fperdomo\App\Url\Scanner;
use Illuminate\Support\Facades\Http;

describe('README Example: Basic Array Scanning', function () {
    test('example from README - scan URLs from array', function () {
        // Mock HTTP responses
        Http::fake([
            'https://example.com' => Http::response('', 200),
            'https://another-example.com' => Http::response('', 404),
        ]);

        // Example from README
        $urls = Scanner::create()
            ->fromArray([
                'https://example.com',
                'https://another-example.com',
            ])->getInvalidUrls();

        expect($urls)->toBeArray()
            ->toHaveCount(1)
            ->and($urls[0]['url'])->toBe('https://another-example.com')
            ->and($urls[0]['status'])->toBe(404);
    });

    test('returns only invalid URLs from array', function () {
        Http::fake([
            'https://valid-site.com' => Http::response('OK', 200),
            'https://broken-site.com' => Http::response('Not Found', 404),
            'https://another-valid.com' => Http::response('OK', 200),
            'https://server-error.com' => Http::response('Error', 500),
        ]);

        $invalidUrls = Scanner::create()
            ->fromArray([
                'https://valid-site.com',
                'https://broken-site.com',
                'https://another-valid.com',
                'https://server-error.com',
            ])->getInvalidUrls();

        expect($invalidUrls)->toHaveCount(2)
            ->and(array_column($invalidUrls, 'url'))->toContain('https://broken-site.com')
            ->and(array_column($invalidUrls, 'url'))->toContain('https://server-error.com');
    });

    test('handles all valid URLs', function () {
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $invalidUrls = Scanner::create()
            ->fromArray([
                'https://example1.com',
                'https://example2.com',
                'https://example3.com',
            ])->getInvalidUrls();

        expect($invalidUrls)->toBeEmpty();
    });

    test('handles all invalid URLs', function () {
        Http::fake([
            '*' => Http::response('Not Found', 404),
        ]);

        $invalidUrls = Scanner::create()
            ->fromArray([
                'https://example1.com',
                'https://example2.com',
            ])->getInvalidUrls();

        expect($invalidUrls)->toHaveCount(2);
    });
});

describe('README Example: File Scanning with Progress', function () {
    beforeEach(function () {
        // Create a temporary CSV file for testing
        $this->csvFile = __DIR__ . '/../fixtures/test_urls.csv';
        $this->tempFiles = [];
    });

    afterEach(function () {
        // Clean up temporary files
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    });

    test('example from README - scan URLs from file with progress callback', function () {
        // Create test CSV file
        $csvFile = __DIR__ . '/../fixtures/urls_sample.csv';
        $this->tempFiles[] = $csvFile;

        if (!is_dir(dirname($csvFile))) {
            mkdir(dirname($csvFile), 0777, true);
        }

        file_put_contents($csvFile, "url\nhttps://example.com\nhttps://google.com\nhttps://invalid.test");

        Http::fake([
            'https://example.com' => Http::response('', 200),
            'https://google.com' => Http::response('', 200),
            'https://invalid.test' => Http::response('', 404),
        ]);

        $progressCallbackCalled = false;

        $scanner = Scanner::create()
            ->fromFile($csvFile)
            ->onProgress(function ($progress) use (&$progressCallbackCalled) {
                $progressCallbackCalled = true;

                // Verify progress object structure
                expect($progress)->toBeInstanceOf(\Fperdomo\App\Data\UrlScanProgress::class)
                    ->and($progress->totalRowsProcessed)->toBeGreaterThan(0)
                    ->and($progress->resultsOk)->toBeInt()
                    ->and($progress->resultsErr)->toBeInt();
            });

        $result = $scanner->scan();

        expect($progressCallbackCalled)->toBeTrue()
            ->and($result->totalRowsProcessed)->toBe(3)
            ->and($result->resultsOk)->toBe(2)
            ->and($result->resultsErr)->toBe(1);
    });

    test('progress callback receives correct percentage calculations', function () {
        $csvFile = __DIR__ . '/../fixtures/urls_progress.csv';
        $this->tempFiles[] = $csvFile;

        if (!is_dir(dirname($csvFile))) {
            mkdir(dirname($csvFile), 0777, true);
        }

        file_put_contents($csvFile, "url\nhttps://valid1.com\nhttps://valid2.com\nhttps://invalid1.com\nhttps://invalid2.com");

        Http::fake([
            'https://valid1.com' => Http::response('', 200),
            'https://valid2.com' => Http::response('', 200),
            'https://invalid1.com' => Http::response('', 404),
            'https://invalid2.com' => Http::response('', 500),
        ]);

        $progressStates = [];

        $scanner = Scanner::create()
            ->fromFile($csvFile)
            ->onProgress(function ($progress) use (&$progressStates) {
                $progressStates[] = [
                    'total' => $progress->totalRowsProcessed,
                    'valid' => $progress->resultsOk,
                    'invalid' => $progress->resultsErr,
                    'validPct' => $progress->validPercentage(),
                    'invalidPct' => $progress->invalidPercentage(),
                ];
            });

        $scanner->scan();

        expect($progressStates)->not->toBeEmpty();

        // Verify the final state
        $finalState = end($progressStates);
        expect($finalState['total'])->toBe(4)
            ->and($finalState['valid'])->toBe(2)
            ->and($finalState['invalid'])->toBe(2)
            ->and($finalState['validPct'])->toBe(50.0)
            ->and($finalState['invalidPct'])->toBe(50.0);
    });
});

describe('README Example: File Scanning with Chunking', function () {
    beforeEach(function () {
        $this->tempFiles = [];
    });

    afterEach(function () {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    });

    test('example from README - scan file with custom chunk size and field', function () {
        $csvFile = __DIR__ . '/../fixtures/urls_chunked.csv';
        $this->tempFiles[] = $csvFile;

        if (!is_dir(dirname($csvFile))) {
            mkdir(dirname($csvFile), 0777, true);
        }

        // Create CSV with multiple URLs
        $urls = array_map(fn($i) => "https://example{$i}.com", range(1, 10));
        file_put_contents($csvFile, "url\n" . implode("\n", $urls));

        Http::fake([
            '*' => Http::response('', 200),
        ]);

        $scanner = Scanner::create()
            ->fromFile($csvFile)
            ->field('url')
            ->chunk(5);

        $result = $scanner->scan();

        expect($result->totalRowsProcessed)->toBe(10)
            ->and($result->resultsOk)->toBe(10)
            ->and($result->resultsErr)->toBe(0);
    });

    test('handles custom field name in CSV', function () {
        $csvFile = __DIR__ . '/../fixtures/urls_custom_field.csv';
        $this->tempFiles[] = $csvFile;

        if (!is_dir(dirname($csvFile))) {
            mkdir(dirname($csvFile), 0777, true);
        }

        file_put_contents($csvFile, "website,name\nhttps://example.com,Example\nhttps://test.com,Test");

        Http::fake([
            'https://example.com' => Http::response('', 200),
            'https://test.com' => Http::response('', 404),
        ]);

        $scanner = Scanner::create()
            ->fromFile($csvFile)
            ->field('website');

        $result = $scanner->scan();

        expect($result->totalRowsProcessed)->toBe(2)
            ->and($result->resultsOk)->toBe(1)
            ->and($result->resultsErr)->toBe(1);
    });

    test('processes large file in chunks efficiently', function () {
        $csvFile = __DIR__ . '/../fixtures/urls_large.csv';
        $this->tempFiles[] = $csvFile;

        if (!is_dir(dirname($csvFile))) {
            mkdir(dirname($csvFile), 0777, true);
        }

        // Create CSV with 100 URLs
        $urls = array_map(fn($i) => "https://site{$i}.com", range(1, 100));
        file_put_contents($csvFile, "url\n" . implode("\n", $urls));

        Http::fake([
            '*' => Http::response('', 200),
        ]);

        $chunkSizes = [];

        $scanner = Scanner::create()
            ->fromFile($csvFile)
            ->chunk(25)
            ->onProgress(function ($progress) use (&$chunkSizes) {
                $chunkSizes[] = $progress->totalRowsProcessed;
            });

        $result = $scanner->scan();

        expect($result->totalRowsProcessed)->toBe(100)
            ->and($chunkSizes)->not->toBeEmpty();
    });
});

describe('Integration: Complete Workflow', function () {
    beforeEach(function () {
        $this->tempFiles = [];
    });

    afterEach(function () {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    });

    test('complete workflow with mixed valid and invalid URLs', function () {
        $csvFile = __DIR__ . '/../fixtures/urls_mixed.csv';
        $this->tempFiles[] = $csvFile;

        if (!is_dir(dirname($csvFile))) {
            mkdir(dirname($csvFile), 0777, true);
        }

        file_put_contents($csvFile, <<<CSV
url
https://valid1.com
https://invalid1.com
https://valid2.com
https://invalid2.com
https://valid3.com
https://error.com
CSV
        );

        Http::fake([
            'https://valid1.com' => Http::response('', 200),
            'https://invalid1.com' => Http::response('', 404),
            'https://valid2.com' => Http::response('', 200),
            'https://invalid2.com' => Http::response('', 403),
            'https://valid3.com' => Http::response('', 200),
            'https://error.com' => Http::response('', 500),
        ]);

        $progressUpdates = [];

        $scanner = Scanner::create()
            ->fromFile($csvFile)
            ->field('url')
            ->chunk(2)
            ->onProgress(function ($progress) use (&$progressUpdates) {
                $progressUpdates[] = sprintf(
                    'Processed: %d, Valid: %.1f%%, Invalid: %.1f%%',
                    $progress->totalRowsProcessed,
                    $progress->validPercentage(),
                    $progress->invalidPercentage()
                );
            });

        $result = $scanner->scan();

        expect($result->totalRowsProcessed)->toBe(6)
            ->and($result->resultsOk)->toBe(3)
            ->and($result->resultsErr)->toBe(3)
            ->and($result->validPercentage())->toBe(50.0)
            ->and($result->invalidPercentage())->toBe(50.0)
            ->and($progressUpdates)->not->toBeEmpty();
    });
});
