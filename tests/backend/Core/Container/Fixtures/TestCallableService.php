<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Container\Fixtures;

class TestCallableService
{
    public function doSomething(string $value): string
    {
        return $value;
    }
}
