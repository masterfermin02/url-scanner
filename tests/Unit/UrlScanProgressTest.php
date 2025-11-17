<?php

use Fperdomo\App\Data\UrlScanProgress;

describe('Data\UrlScanProgress', function () {
    test('can be instantiated with progress data', function () {
        $progress = new UrlScanProgress(
            totalRowsProcessed: 100,
            resultsOk: 80,
            resultsErr: 20,
        );

        expect($progress->totalRowsProcessed)->toBe(100)
            ->and($progress->resultsOk)->toBe(80)
            ->and($progress->resultsErr)->toBe(20);
    });

    test('calculates valid percentage correctly', function () {
        $progress = new UrlScanProgress(
            totalRowsProcessed: 100,
            resultsOk: 75,
            resultsErr: 25,
        );

        expect($progress->validPercentage())->toBe(75.0);
    });

    test('calculates invalid percentage correctly', function () {
        $progress = new UrlScanProgress(
            totalRowsProcessed: 100,
            resultsOk: 75,
            resultsErr: 25,
        );

        expect($progress->invalidPercentage())->toBe(25.0);
    });

    test('handles zero total rows without division by zero', function () {
        $progress = new UrlScanProgress(
            totalRowsProcessed: 0,
            resultsOk: 0,
            resultsErr: 0,
        );

        expect($progress->validPercentage())->toBe(0.0)
            ->and($progress->invalidPercentage())->toBe(0.0);
    });

    test('calculates percentages with decimal precision', function () {
        $progress = new UrlScanProgress(
            totalRowsProcessed: 3,
            resultsOk: 2,
            resultsErr: 1,
        );

        expect($progress->validPercentage())->toBeFloat()
            ->toEqualWithDelta(66.666, 0.01)
            ->and($progress->invalidPercentage())->toEqualWithDelta(33.333, 0.01);
    });

    test('handles all URLs valid scenario', function () {
        $progress = new UrlScanProgress(
            totalRowsProcessed: 50,
            resultsOk: 50,
            resultsErr: 0,
        );

        expect($progress->validPercentage())->toBe(100.0)
            ->and($progress->invalidPercentage())->toBe(0.0);
    });

    test('handles all URLs invalid scenario', function () {
        $progress = new UrlScanProgress(
            totalRowsProcessed: 50,
            resultsOk: 0,
            resultsErr: 50,
        );

        expect($progress->validPercentage())->toBe(0.0)
            ->and($progress->invalidPercentage())->toBe(100.0);
    });
});
