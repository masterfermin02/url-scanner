# Test Documentation

## Overview
This document describes the test scenarios and structure for the URL Scanner package based on the README examples.

## Test Structure

### Unit Tests (`tests/Unit/`)
Unit tests focus on testing individual components in isolation.

#### HttpScannerTest.php
Tests the low-level HTTP scanner that checks URL status codes.

**Scenarios:**
- ✅ Can be instantiated with an array of URLs
- ✅ Returns empty array when all URLs are valid (HTTP 200)
- ✅ Identifies URLs with 404 status as invalid
- ✅ Identifies URLs with 500 status as invalid
- ✅ Handles multiple invalid URLs
- ✅ Handles exceptions by returning 500 status
- ✅ Correctly categorizes client errors (4xx) as invalid
- ✅ Returns URLs with their corresponding status codes

#### ArrayScannerTest.php
Tests the array-based URL scanner.

**Scenarios:**
- ✅ Can be created from an array of URLs
- ✅ Can be instantiated directly
- ✅ Returns invalid URLs from array
- ✅ Returns empty array when all URLs are valid
- ✅ Handles empty array
- ✅ Processes multiple URLs correctly

#### UrlScanProgressTest.php
Tests the progress tracking data object.

**Scenarios:**
- ✅ Can be instantiated with progress data
- ✅ Calculates valid percentage correctly
- ✅ Calculates invalid percentage correctly
- ✅ Handles zero total rows without division by zero
- ✅ Calculates percentages with decimal precision
- ✅ Handles all URLs valid scenario (100% valid)
- ✅ Handles all URLs invalid scenario (100% invalid)

#### UrlScannerTest.php
Tests the main Scanner factory class.

**Scenarios:**
- ✅ Can create a new scanner instance
- ✅ fromArray returns ArrayScanner instance
- ✅ fromFile returns FileScanner instance
- ✅ Provides fluent interface for chaining

### Feature Tests (`tests/Feature/`)
Feature tests verify complete workflows and README examples.

#### ReadmeExamplesTest.php
Tests all examples from the README documentation.

**Scenario Groups:**

##### 1. Basic Array Scanning
```php
Scanner::create()->fromArray([...])->getInvalidUrls();
```
- ✅ Example from README - scan URLs from array
- ✅ Returns only invalid URLs from array
- ✅ Handles all valid URLs
- ✅ Handles all invalid URLs

##### 2. File Scanning with Progress
```php
Scanner::create()
    ->fromFile('url.csv')
    ->onProgress(function ($progress) {})
    ->scan();
```
- ✅ Example from README - scan URLs from file with progress callback
- ✅ Progress callback receives correct percentage calculations
- ✅ Verifies UrlScanProgress object structure
- ✅ Tracks valid/invalid percentages

##### 3. File Scanning with Chunking
```php
Scanner::create()
    ->fromFile('url.csv')
    ->field('url')
    ->chunk(1000)
    ->onProgress(function ($progress) {})
    ->scan();
```
- ✅ Example from README - scan file with custom chunk size and field
- ✅ Handles custom field name in CSV
- ✅ Processes large file in chunks efficiently
- ✅ Tests chunk size of 25 with 100 URLs

##### 4. Integration: Complete Workflow
- ✅ Complete workflow with mixed valid and invalid URLs
- ✅ Tests all components working together
- ✅ Verifies progress updates throughout scanning
- ✅ Validates final statistics

## Running Tests

### Run All Tests
```bash
composer test
```

Or using Pest directly:
```bash
vendor/bin/pest
```

### Run Specific Test Suite
```bash
# Run only unit tests
vendor/bin/pest tests/Unit

# Run only feature tests
vendor/bin/pest tests/Feature

# Run specific test file
vendor/bin/pest tests/Feature/ReadmeExamplesTest.php
```

### Run Tests with Coverage
```bash
vendor/bin/pest --coverage
```

### Run Tests in Parallel
```bash
vendor/bin/pest --parallel
```

## Test Fixtures

Test fixtures are located in `tests/fixtures/` and include sample CSV files:
- `test_urls.csv` - Basic URL list
- `urls_sample.csv` - Sample URLs for progress testing
- `urls_progress.csv` - URLs for percentage calculations
- `urls_chunked.csv` - Large dataset for chunk testing
- `urls_custom_field.csv` - CSV with custom field names
- `urls_large.csv` - 100 URLs for performance testing
- `urls_mixed.csv` - Mixed valid/invalid URLs

Fixtures are automatically created and cleaned up during tests.

## HTTP Mocking

All tests use Laravel's `Http::fake()` to mock HTTP responses without making real network requests:

```php
Http::fake([
    'https://example.com' => Http::response('', 200),
    'https://invalid-url.com' => Http::response('', 404),
]);
```

## Test Coverage Goals

- **Unit Tests**: 100% coverage of individual classes
- **Feature Tests**: All README examples covered
- **Edge Cases**: Empty arrays, zero division, exceptions
- **Integration**: Complete workflows tested

## Writing New Tests

When adding new features, follow these guidelines:

1. **Unit Tests**: Test each method in isolation
2. **Feature Tests**: Test user-facing workflows
3. **Use Descriptive Names**: Test names should describe what they verify
4. **Mock External Services**: Use `Http::fake()` for HTTP calls
5. **Clean Up**: Remove temporary files in `afterEach` hooks
6. **Assertions**: Use Pest's expect() syntax for readability

### Example Test Structure
```php
describe('Feature Name', function () {
    beforeEach(function () {
        // Setup
    });

    afterEach(function () {
        // Cleanup
    });

    test('should do something specific', function () {
        // Arrange
        // Act
        // Assert
    });
});
```

## Continuous Integration

Tests should be run automatically on:
- Every commit
- Pull requests
- Before deployment

Add to your CI/CD pipeline:
```yaml
- name: Run tests
  run: composer test
```
