<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Exception;

use Lwt\Core\Exception\DatabaseException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the DatabaseException class.
 *
 * @covers \Lwt\Core\Exception\DatabaseException
 */
class DatabaseExceptionTest extends TestCase
{
    public function testConstructorWithMessage(): void
    {
        $exception = new DatabaseException('Database error');

        $this->assertSame('Database error', $exception->getMessage());
        $this->assertSame(500, $exception->getHttpStatusCode());
    }

    public function testConstructorWithQuery(): void
    {
        $exception = new DatabaseException(
            'Query failed',
            0,
            null,
            'SELECT * FROM users WHERE id = 1',
            1045,
            'HY000'
        );

        $this->assertNotNull($exception->getQuery());
        $this->assertSame(1045, $exception->getSqlErrorCode());
        $this->assertSame('HY000', $exception->getSqlState());

        $context = $exception->getContext();
        $this->assertArrayHasKey('query', $context);
        $this->assertArrayHasKey('sql_error_code', $context);
        $this->assertArrayHasKey('sql_state', $context);
    }

    public function testConnectionFailed(): void
    {
        $exception = DatabaseException::connectionFailed(
            'localhost',
            'mydb',
            'Access denied for user'
        );

        $this->assertStringContainsString('Failed to connect', $exception->getMessage());
        $this->assertStringContainsString('localhost', $exception->getMessage());
        $this->assertStringContainsString('mydb', $exception->getMessage());

        $context = $exception->getContext();
        $this->assertSame('localhost', $context['host']);
        $this->assertSame('mydb', $context['database']);
    }

    public function testQueryFailed(): void
    {
        $exception = DatabaseException::queryFailed(
            'SELECT * FROM nonexistent',
            "Table 'nonexistent' doesn't exist",
            1146,
            '42S02'
        );

        $this->assertStringContainsString('Query failed', $exception->getMessage());
        $this->assertSame(1146, $exception->getSqlErrorCode());
        $this->assertSame('42S02', $exception->getSqlState());
    }

    public function testPrepareFailed(): void
    {
        $exception = DatabaseException::prepareFailed(
            'SELECT * FROM users WHERE ?',
            'Syntax error'
        );

        $this->assertStringContainsString('Failed to prepare statement', $exception->getMessage());
    }

    public function testTransactionFailed(): void
    {
        $exception = DatabaseException::transactionFailed(
            'commit',
            'Deadlock detected'
        );

        $this->assertStringContainsString('Transaction commit failed', $exception->getMessage());

        $context = $exception->getContext();
        $this->assertSame('commit', $context['transaction_operation']);
    }

    public function testForeignKeyViolation(): void
    {
        $exception = DatabaseException::foreignKeyViolation(
            'words',
            'fk_words_language',
            'Cannot delete or update a parent row'
        );

        $this->assertStringContainsString('Foreign key constraint', $exception->getMessage());
        $this->assertSame(409, $exception->getHttpStatusCode());

        $context = $exception->getContext();
        $this->assertSame('words', $context['table']);
        $this->assertSame('fk_words_language', $context['constraint']);
    }

    public function testDuplicateEntry(): void
    {
        $exception = DatabaseException::duplicateEntry(
            'users',
            'email',
            'test@example.com'
        );

        $this->assertStringContainsString('Duplicate entry', $exception->getMessage());
        $this->assertSame(409, $exception->getHttpStatusCode());

        $context = $exception->getContext();
        $this->assertSame('users', $context['table']);
        $this->assertSame('email', $context['column']);
        $this->assertSame('test@example.com', $context['value']);
    }

    public function testRecordNotFound(): void
    {
        $exception = DatabaseException::recordNotFound('texts', 'TxID', 999);

        $this->assertStringContainsString('Record with TxID=999', $exception->getMessage());
        $this->assertSame(404, $exception->getHttpStatusCode());

        $context = $exception->getContext();
        $this->assertSame('texts', $context['table']);
        $this->assertSame('TxID', $context['key']);
        $this->assertSame(999, $context['id']);
    }

    public function testGetUserMessage(): void
    {
        $exception = new DatabaseException('Internal SQL error details');

        $this->assertSame(
            'A database error occurred. Please try again later.',
            $exception->getUserMessage()
        );
    }

    public function testQuerySanitization(): void
    {
        // Long query should be truncated
        $longQuery = 'SELECT * FROM users WHERE name = \'' . str_repeat('a', 2000) . '\'';
        $exception = new DatabaseException('Error', 0, null, $longQuery);

        $context = $exception->getContext();
        $this->assertLessThan(1100, strlen($context['query']));
        $this->assertStringContainsString('[TRUNCATED]', $context['query']);
    }
}
