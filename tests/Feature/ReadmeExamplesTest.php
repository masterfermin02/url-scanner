<?php

use Fperdomo\App\Url\Scanner;
use Illuminate\Support\Facades\Http;
use Spatie\SimpleExcel\SimpleExcelWriter;

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    // Create a temporary CSV file for testing
    $this->temporaryDirectory = new \Spatie\TemporaryDirectory\TemporaryDirectory( __DIR__ . '/temp');
    $this->csvFile = $this->temporaryDirectory->path('urls_mixed.csv');
});

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
    test('example from README - scan URLs from file with progress callback', function () {
        // Create test CSV file
        SimpleExcelWriter::create($this->csvFile)
        ->addRow(['url' => 'https://example.com'])
        ->addRow(['url' => 'https://google.com'])
        ->addRow(['url' => 'https://invalid.test']);

        Http::fake([
            'https://example.com' => Http::response('', 200),
            'https://google.com' => Http::response('', 200),
            'https://invalid.test' => Http::response('', 404),
        ]);

        $progressCallbackCalled = false;

        $scanner = Scanner::create()
            ->fromFile($this->csvFile)
            ->onProgress(function ($progress) use (&$progressCallbackCalled) {
                $progressCallbackCalled = true;

                // Verify progress object structure
                expect($progress)->toBeInstanceOf(\Fperdomo\App\Data\UrlScanProgress::class)
                    ->and($progress->totalRowsProcessed)->toBeGreaterThan(0)
                    ->and($progress->resultsOk)->toBeInt()
                    ->and($progress->resultsErr)->toBeInt();
            });

        $result = await($scanner->scan());
        expect($result->totalRowsProcessed)->toBe(3)
            ->and($result->resultsOk)->toBe(2)
            ->and($result->resultsErr)->toBe(1);
    });
});

describe('README Example: File Scanning with Chunking', function () {
    test('example from README - scan file with custom chunk size and field', function () {
        // Create CSV with multiple URLs
        $file = SimpleExcelWriter::create($this->csvFile);
        foreach (range(1, 10) as $i) {
            $file->addRow(['url' => "https://example{$i}.com"]);
        }


        Http::fake([
            '*' => Http::response('', 200),
        ]);

        $scanner = Scanner::create()
            ->fromFile($this->csvFile)
            ->field('url')
            ->chunk(5);
        $result = await($scanner->scan());
        expect($result->totalRowsProcessed)->toBe(10)
            ->and($result->resultsOk)->toBe(10)
            ->and($result->resultsErr)->toBe(0);
    });

    test('handles custom field name in CSV', function () {
       SimpleExcelWriter::create($this->csvFile)
            ->addRow(['website' => 'https://example.com', 'name' => 'Example'])
            ->addRow(['website' => 'https://test.com', 'name' => 'Test']);

        Http::fake([
            'https://example.com' => Http::response('', 200),
            'https://test.com' => Http::response('', 404),
        ]);

        $scanner = Scanner::create()
            ->fromFile($this->csvFile)
            ->field('website');

        $result = await($scanner->scan());
        expect($result->totalRowsProcessed)->toBe(2)
            ->and($result->resultsOk)->toBe(1)
            ->and($result->resultsErr)->toBe(1);
    });

    test('processes large file in chunks efficiently', function () {
        $file = SimpleExcelWriter::create($this->csvFile);

        foreach (range(1, 100) as $i) {
            $file->addRow(['url' => "https://site{$i}.com"]);
        }

        Http::fake([
            '*' => Http::response('', 200),
        ]);

        $chunkSizes = [];

        $scanner = Scanner::create()
            ->fromFile($this->csvFile)
            ->chunk(25)
            ->onProgress(function ($progress) use (&$chunkSizes) {
                $chunkSizes[] = $progress->totalRowsProcessed;
            });

        $result = await($scanner->scan());
        expect($result->totalRowsProcessed)->toBe(100);
    });
});

describe('Integration: Complete Workflow', function () {
    test('complete workflow with mixed valid and invalid URLs', function () {
        SimpleExcelWriter::create($this->csvFile)->addRow(
            ['url' => 'https://valid1.com']
        )
        ->addRow(
            ['url' => 'https://invalid1.com'],
        )
        ->addRow(
            ['url' => 'https://valid2.com'],
        )
        ->addRow(
            ['url' => 'https://invalid2.com'],
        )
        ->addRow(
            ['url' => 'https://valid3.com'],
        )
        ->addRow(
            ['url' => 'https://error.com'],
        );

        assertMatchesFileSnapshot($this->csvFile);

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
            ->fromFile($this->csvFile)
            ->field('url')
            ->chunk(2)
            ->onProgress(function (\Fperdomo\App\Data\UrlScanProgress $progress) use (&$progressUpdates) {
                $progressUpdates[] = sprintf(
                    'Processed: %d, Valid: %.1f%%, Invalid: %.1f%%',
                    $progress->totalRowsProcessed,
                    $progress->validPercentage(),
                    $progress->invalidPercentage()
                );
            });

        $result = await($scanner->scan());
        expect($result->totalRowsProcessed)->toBe(6)
            ->and($result->resultsOk)->toBe(3)
            ->and($result->resultsErr)->toBe(3)
            ->and($result->validPercentage())->toBe(50.0)
            ->and($result->invalidPercentage())->toBe(50.0);
    });
});
