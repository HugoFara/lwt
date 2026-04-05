<?php

declare(strict_types=1);

namespace Lwt\Tests\Controllers;

use Lwt\Shared\Http\BaseController;

/**
 * Concrete implementation of BaseController for testing.
 *
 * Exposes protected methods for testing purposes.
 */
class TestableController extends BaseController
{
    public function testParam(string $key, string $default = ''): string
    {
        return $this->param($key, $default);
    }

    public function testGet(string $key, string $default = ''): string
    {
        return $this->get($key, $default);
    }

    public function testPost(string $key, string $default = ''): string
    {
        return $this->post($key, $default);
    }

    public function testIsPost(): bool
    {
        return $this->isPost();
    }

    public function testIsGet(): bool
    {
        return $this->isGet();
    }

    public function testQuery(string $sql): \mysqli_result|bool
    {
        return $this->query($sql);
    }

    public function testExecute(string $sql): int
    {
        return $this->execute($sql);
    }

    public function testGetValue(string $sql): mixed
    {
        return $this->getValue($sql);
    }

    public function testGetMarkedIds(string|array $marked): array
    {
        return $this->getMarkedIds($marked);
    }
}
