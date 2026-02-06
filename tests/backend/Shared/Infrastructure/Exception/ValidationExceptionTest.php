<?php

declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Exception;

use Lwt\Shared\Infrastructure\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ValidationException class.
 *
 * @covers \Lwt\Shared\Infrastructure\Exception\ValidationException
 */
class ValidationExceptionTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $exception = new ValidationException();

        $this->assertSame('Validation failed', $exception->getMessage());
        $this->assertSame(422, $exception->getHttpStatusCode());
        $this->assertFalse($exception->shouldLog());
        $this->assertSame([], $exception->getErrors());
    }

    public function testConstructorWithErrors(): void
    {
        $errors = [
            'email' => ['Invalid email format'],
            'name' => ['Name is required', 'Name must be at least 2 characters'],
        ];
        $exception = new ValidationException('Custom message', $errors);

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame($errors, $exception->getErrors());
    }

    public function testForField(): void
    {
        $exception = ValidationException::forField('email', 'Invalid email format', 'not-an-email');

        $this->assertStringContainsString('email', $exception->getMessage());
        $this->assertSame('email', $exception->getField());
        $this->assertSame(['email' => ['Invalid email format']], $exception->getErrors());

        $context = $exception->getContext();
        $this->assertSame('not-an-email', $context['value']);
    }

    public function testRequiredField(): void
    {
        $exception = ValidationException::requiredField('username');

        $this->assertSame('username', $exception->getField());
        $this->assertSame('This field is required', $exception->getFieldErrors('username')[0]);
    }

    public function testInvalidType(): void
    {
        $exception = ValidationException::invalidType('age', 'integer', 'string');

        $this->assertStringContainsString('Expected integer, got string', $exception->getMessage());
    }

    public function testOutOfRangeWithMinAndMax(): void
    {
        $exception = ValidationException::outOfRange('status', 1, 5, 10);

        $this->assertStringContainsString('between 1 and 5', $exception->getMessage());
    }

    public function testOutOfRangeWithMinOnly(): void
    {
        $exception = ValidationException::outOfRange('count', 1, null, 0);

        $this->assertStringContainsString('at least 1', $exception->getMessage());
    }

    public function testOutOfRangeWithMaxOnly(): void
    {
        $exception = ValidationException::outOfRange('items', null, 100, 150);

        $this->assertStringContainsString('at most 100', $exception->getMessage());
    }

    public function testInvalidLengthWithMinAndMax(): void
    {
        $exception = ValidationException::invalidLength('password', 8, 50, 5);

        $this->assertStringContainsString('between 8 and 50', $exception->getMessage());
        $this->assertStringContainsString('got 5', $exception->getMessage());
    }

    public function testInvalidLengthWithMinOnly(): void
    {
        $exception = ValidationException::invalidLength('username', 3, null, 2);

        $this->assertStringContainsString('at least 3', $exception->getMessage());
    }

    public function testInvalidLengthWithMaxOnly(): void
    {
        $exception = ValidationException::invalidLength('bio', null, 500, 600);

        $this->assertStringContainsString('at most 500', $exception->getMessage());
    }

    public function testInvalidEnum(): void
    {
        $exception = ValidationException::invalidEnum('status', [1, 2, 3, 98, 99], 50);

        $this->assertStringContainsString('one of', $exception->getMessage());

        $context = $exception->getContext();
        $this->assertSame(50, $context['value']);
    }

    public function testInvalidEnumWithStrings(): void
    {
        $exception = ValidationException::invalidEnum('role', ['admin', 'user', 'guest'], 'superuser');

        $this->assertStringContainsString('"admin"', $exception->getMessage());
        $this->assertStringContainsString('"user"', $exception->getMessage());
        $this->assertStringContainsString('"guest"', $exception->getMessage());
    }

    public function testInvalidUrl(): void
    {
        $exception = ValidationException::invalidUrl('website', 'not-a-url');

        $this->assertStringContainsString('Invalid URL', $exception->getMessage());
    }

    public function testInvalidEmail(): void
    {
        $exception = ValidationException::invalidEmail('email', 'not-an-email');

        $this->assertStringContainsString('Invalid email', $exception->getMessage());
    }

    public function testEntityNotFound(): void
    {
        $exception = ValidationException::entityNotFound('language_id', 'language', 999);

        $this->assertStringContainsString('Language with ID 999 not found', $exception->getMessage());
        $this->assertSame(404, $exception->getHttpStatusCode());
    }

    public function testWithErrors(): void
    {
        $errors = [
            'field1' => ['Error 1'],
            'field2' => ['Error 2', 'Error 3'],
        ];
        $exception = ValidationException::withErrors($errors);

        $this->assertStringContainsString('3 error(s)', $exception->getMessage());
        $this->assertSame($errors, $exception->getErrors());
    }

    public function testGetFieldErrors(): void
    {
        $exception = new ValidationException('Error', [
            'email' => ['Error 1', 'Error 2'],
            'name' => ['Error 3'],
        ]);

        $this->assertSame(['Error 1', 'Error 2'], $exception->getFieldErrors('email'));
        $this->assertSame(['Error 3'], $exception->getFieldErrors('name'));
        $this->assertSame([], $exception->getFieldErrors('nonexistent'));
    }

    public function testHasFieldError(): void
    {
        $exception = new ValidationException('Error', [
            'email' => ['Error 1'],
        ]);

        $this->assertTrue($exception->hasFieldError('email'));
        $this->assertFalse($exception->hasFieldError('name'));
    }

    public function testAddError(): void
    {
        $exception = new ValidationException();
        $result = $exception->addError('field1', 'Error 1')
                           ->addError('field1', 'Error 2')
                           ->addError('field2', 'Error 3');

        $this->assertSame($exception, $result);
        $this->assertSame(['Error 1', 'Error 2'], $exception->getFieldErrors('field1'));
        $this->assertSame(['Error 3'], $exception->getFieldErrors('field2'));
    }

    public function testGetFirstError(): void
    {
        $exception = new ValidationException('Error', [
            'email' => ['First error', 'Second error'],
            'name' => ['Third error'],
        ]);

        $this->assertSame('First error', $exception->getFirstError());
    }

    public function testGetFirstErrorWhenEmpty(): void
    {
        $exception = new ValidationException();

        $this->assertNull($exception->getFirstError());
    }

    public function testGetUserMessage(): void
    {
        $exception = new ValidationException('Error', [
            'email' => ['Please enter a valid email address'],
        ]);

        $this->assertSame('Please enter a valid email address', $exception->getUserMessage());
    }

    public function testGetUserMessageWhenEmpty(): void
    {
        $exception = new ValidationException();

        $this->assertSame('Validation failed. Please check your input.', $exception->getUserMessage());
    }

    public function testToApiResponse(): void
    {
        $errors = ['email' => ['Invalid email']];
        $exception = new ValidationException('Validation failed', $errors);

        $response = $exception->toApiResponse();

        $this->assertSame('Validation failed', $response['message']);
        $this->assertSame($errors, $response['errors']);
    }
}
