<?php

namespace Tests;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up Laravel HTTP facade for testing
        $this->setupHttpFacade();
    }

    protected function setupHttpFacade(): void
    {
        // Simply swap the Http facade with a new factory instance
        Http::swap(new HttpFactory());
    }
}
