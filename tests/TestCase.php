<?php

namespace Tests;

use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // config/app.php pins env to "development"; Laravel only lets prompts run
        // without a TTY (e.g. CI on Linux) when runningUnitTests() is true.
        $this->app->detectEnvironment(fn () => 'testing');
    }
}
