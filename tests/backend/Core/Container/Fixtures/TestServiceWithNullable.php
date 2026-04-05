<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Container\Fixtures;

class TestServiceWithNullable
{
    public function __construct(
        public ?string $value = null
    ) {
    }
}
