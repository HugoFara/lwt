<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Container;

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Container/ContainerInterface.php';
require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Container/ContainerException.php';
require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Container/NotFoundException.php';
require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Container/Container.php';

use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Container\ContainerException;
use Lwt\Shared\Infrastructure\Container\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DI Container.
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
        Container::setInstance(null);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    // ===== Basic binding tests =====

    public function testBindAndGet(): void
    {
        $this->container->bind('test', fn() => 'value');

        $this->assertEquals('value', $this->container->get('test'));
    }

    public function testBindCreatesNewInstanceEachTime(): void
    {
        $counter = 0;
        $this->container->bind('counter', function () use (&$counter) {
            return ++$counter;
        });

        $this->assertEquals(1, $this->container->get('counter'));
        $this->assertEquals(2, $this->container->get('counter'));
        $this->assertEquals(3, $this->container->get('counter'));
    }

    // ===== Singleton tests =====

    public function testSingletonReturnsSharedInstance(): void
    {
        $counter = 0;
        $this->container->singleton('singleton', function () use (&$counter) {
            return ++$counter;
        });

        $this->assertEquals(1, $this->container->get('singleton'));
        $this->assertEquals(1, $this->container->get('singleton'));
        $this->assertEquals(1, $this->container->get('singleton'));
    }

    public function testSingletonReturnsSameObject(): void
    {
        $this->container->singleton('object', fn() => new \stdClass());

        $first = $this->container->get('object');
        $second = $this->container->get('object');

        $this->assertSame($first, $second);
    }

    // ===== Instance tests =====

    public function testInstanceStoresExistingObject(): void
    {
        $obj = new \stdClass();
        $obj->value = 'test';

        $this->container->instance('myObject', $obj);

        $retrieved = $this->container->get('myObject');
        $this->assertSame($obj, $retrieved);
        $this->assertEquals('test', $retrieved->value);
    }

    public function testInstanceCanStoreScalars(): void
    {
        $this->container->instance('string', 'hello');
        $this->container->instance('array', ['a', 'b', 'c']);

        $this->assertEquals('hello', $this->container->get('string'));
        $this->assertEquals(['a', 'b', 'c'], $this->container->get('array'));
    }

    // ===== has() tests =====

    public function testHasReturnsTrueForBoundService(): void
    {
        $this->container->bind('test', fn() => 'value');

        $this->assertTrue($this->container->has('test'));
    }

    public function testHasReturnsTrueForInstance(): void
    {
        $this->container->instance('test', 'value');

        $this->assertTrue($this->container->has('test'));
    }

    public function testHasReturnsFalseForUnknownService(): void
    {
        $this->assertFalse($this->container->has('unknown'));
    }

    public function testHasReturnsTrueForAutoWirableClass(): void
    {
        $this->assertTrue($this->container->has(\stdClass::class));
    }

    // ===== Alias tests =====

    public function testAliasResolvesToOriginal(): void
    {
        $this->container->instance('original', 'value');
        $this->container->alias('alias', 'original');

        $this->assertEquals('value', $this->container->get('alias'));
    }

    public function testAliasChain(): void
    {
        $this->container->instance('original', 'value');
        $this->container->alias('alias1', 'original');
        $this->container->alias('alias2', 'alias1');

        $this->assertEquals('value', $this->container->get('alias2'));
    }

    public function testHasReturnsTrueForAlias(): void
    {
        $this->container->instance('original', 'value');
        $this->container->alias('alias', 'original');

        $this->assertTrue($this->container->has('alias'));
    }

    // ===== Auto-wiring tests =====

    public function testAutoWiresClassWithNoConstructor(): void
    {
        $obj = $this->container->get(\stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $obj);
    }

    public function testAutoWiresClassWithDependencies(): void
    {
        // Create a simple class with dependencies
        $obj = $this->container->get(TestServiceWithDeps::class);

        $this->assertInstanceOf(TestServiceWithDeps::class, $obj);
        $this->assertInstanceOf(TestDependency::class, $obj->dependency);
    }

    public function testAutoWiresWithDefaultValues(): void
    {
        $obj = $this->container->get(TestServiceWithDefaults::class);

        $this->assertInstanceOf(TestServiceWithDefaults::class, $obj);
        $this->assertEquals('default', $obj->value);
    }

    public function testAutoWiresWithNullableTypes(): void
    {
        $obj = $this->container->get(TestServiceWithNullable::class);

        $this->assertInstanceOf(TestServiceWithNullable::class, $obj);
        $this->assertNull($obj->value);
    }

    // ===== Exception tests =====

    public function testThrowsNotFoundExceptionForUnknownService(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("Service 'unknown' not found");

        $this->container->get('unknown');
    }

    public function testThrowsExceptionForCircularDependency(): void
    {
        $this->container->singleton('a', fn($c) => $c->get('b'));
        $this->container->singleton('b', fn($c) => $c->get('a'));

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency');

        $this->container->get('a');
    }

    public function testThrowsExceptionForNonInstantiableClass(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('not instantiable');

        $this->container->get(TestAbstractClass::class);
    }

    // ===== Global instance tests =====

    public function testGetInstanceReturnsContainer(): void
    {
        $container = Container::getInstance();

        $this->assertInstanceOf(Container::class, $container);
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $first = Container::getInstance();
        $second = Container::getInstance();

        $this->assertSame($first, $second);
    }

    public function testSetInstanceOverridesGlobal(): void
    {
        $custom = new Container();
        Container::setInstance($custom);

        $this->assertSame($custom, Container::getInstance());
    }

    // ===== make() tests =====

    public function testMakeCreatesNewInstance(): void
    {
        $first = $this->container->make(\stdClass::class);
        $second = $this->container->make(\stdClass::class);

        $this->assertNotSame($first, $second);
    }

    public function testMakeWithParameters(): void
    {
        $obj = $this->container->make(
            TestServiceWithDefaults::class,
            ['value' => 'custom']
        );

        $this->assertEquals('custom', $obj->value);
    }

    // ===== call() tests =====

    public function testCallInvokesMethod(): void
    {
        $this->container->instance(TestCallableService::class, new TestCallableService());

        $result = $this->container->call(
            TestCallableService::class,
            'doSomething',
            ['value' => 'test']
        );

        $this->assertEquals('test', $result);
    }

    // ===== reset() tests =====

    public function testResetClearsContainer(): void
    {
        $this->container->instance('test', 'value');
        $this->container->bind('factory', fn() => 'created');
        $this->container->alias('alias', 'test');

        $this->container->reset();

        $this->assertFalse($this->container->has('test'));
        $this->assertFalse($this->container->has('factory'));
        $this->assertFalse($this->container->has('alias'));
    }

    // ===== getRegisteredServices() tests =====

    public function testGetRegisteredServicesReturnsAllServices(): void
    {
        $this->container->instance('inst', 'value');
        $this->container->bind('bind', fn() => 'value');
        $this->container->singleton('singleton', fn() => 'value');

        $services = $this->container->getRegisteredServices();

        $this->assertContains('inst', $services);
        $this->assertContains('bind', $services);
        $this->assertContains('singleton', $services);
    }

    // ===== Factory receives container tests =====

    public function testFactoryReceivesContainer(): void
    {
        $this->container->instance('config', ['key' => 'value']);
        $this->container->bind('service', function (Container $c) {
            return $c->get('config');
        });

        $result = $this->container->get('service');

        $this->assertEquals(['key' => 'value'], $result);
    }
}

// Test helper classes

class TestDependency
{
    public string $value = 'dependency';
}

class TestServiceWithDeps
{
    public function __construct(
        public TestDependency $dependency
    ) {
    }
}

class TestServiceWithDefaults
{
    public function __construct(
        public string $value = 'default'
    ) {
    }
}

class TestServiceWithNullable
{
    public function __construct(
        public ?string $value = null
    ) {
    }
}

abstract class TestAbstractClass
{
}

class TestCallableService
{
    public function doSomething(string $value): string
    {
        return $value;
    }
}
