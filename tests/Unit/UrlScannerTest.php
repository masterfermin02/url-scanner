<?php

use Fperdomo\App\Url\Scanner;
use Fperdomo\App\Url\FileScanner;
use Fperdomo\App\Url\ArrayScanner;

describe('Url\Scanner', function () {
    test('can create a new scanner instance', function () {
        $scanner = Scanner::create();

        expect($scanner)->toBeInstanceOf(Scanner::class);
    });

    test('fromArray returns ArrayScanner instance', function () {
        $scanner = Scanner::create();
        $arrayScanner = $scanner->fromArray(['https://example.com']);

        expect($arrayScanner)->toBeInstanceOf(ArrayScanner::class);
    });

    test('fromFile returns FileScanner instance', function () {
        // Create a temporary test file
        $tempDir = new Spatie\TemporaryDirectory\TemporaryDirectory(__DIR__ . '/temp');
        $csvFile = $tempDir->path('test_scanner.csv');
        \Spatie\SimpleExcel\SimpleExcelWriter::create($csvFile)
        ->addRow(['url' => 'https://example.com']);

        $scanner = Scanner::create();
        $fileScanner = $scanner->fromFile($csvFile);

        expect($fileScanner)->toBeInstanceOf(FileScanner::class);
    });

    test('provides fluent interface for chaining', function () {
        $result = Scanner::create()->fromArray([
            'https://example.com',
        ]);

        expect($result)->toBeInstanceOf(ArrayScanner::class);
    });
});
