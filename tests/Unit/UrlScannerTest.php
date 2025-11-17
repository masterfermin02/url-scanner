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
        $csvFile = __DIR__ . '/../fixtures/test_scanner.csv';

        if (!is_dir(dirname($csvFile))) {
            mkdir(dirname($csvFile), 0777, true);
        }

        file_put_contents($csvFile, "url\nhttps://example.com");

        $scanner = Scanner::create();
        $fileScanner = $scanner->fromFile($csvFile);

        expect($fileScanner)->toBeInstanceOf(FileScanner::class);

        // Cleanup
        if (file_exists($csvFile)) {
            unlink($csvFile);
        }
    });

    test('provides fluent interface for chaining', function () {
        $result = Scanner::create()->fromArray([
            'https://example.com',
        ]);

        expect($result)->toBeInstanceOf(ArrayScanner::class);
    });
});
