<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Database;

use Lwt\Shared\Infrastructure\Database\SchemaConstraints;
use PHPUnit\Framework\TestCase;

/**
 * `SchemaConstraints::FOREIGN_KEYS` must say exactly what the schema files say.
 *
 * The list exists to repair installs that lost constraints to an upgrade
 * (#272, #273), so it is only worth anything while it matches the migrations.
 * A new table whose foreign keys are not listed here would be created correctly
 * on a fresh install and then never repaired anywhere else, which is precisely
 * the failure this list is meant to end.
 *
 * These tests read db/schema/baseline.sql and db/migrations/*.sql and compare.
 */
class SchemaConstraintsTest extends TestCase
{
    /**
     * Foreign keys declared across the schema files, keyed by constraint name.
     *
     * Later definitions win, the way a later migration supersedes an earlier
     * one.
     *
     * @return array<string, array{name: string, table: string, columns: array<string>,
     *     refTable: string, refColumns: array<string>, onDelete: string}>
     */
    private function declaredInSchemaFiles(): array
    {
        // tests/backend/Core/Database → repo root is four levels up.
        $root = dirname(__DIR__, 4);
        $files = array_merge(
            [$root . '/db/schema/baseline.sql'],
            glob($root . '/db/migrations/*.sql') ?: []
        );
        sort($files);

        $found = [];
        foreach ($files as $file) {
            $sql = (string) file_get_contents($file);
            // Drop comments so a constraint that is only described in prose
            // (settings has one) does not count as declared.
            $sql = (string) preg_replace('/^--.*$/m', '', $sql);

            foreach (explode(';', $sql) as $statement) {
                if (
                    !preg_match(
                        '/(?:CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?|ALTER\s+TABLE\s+)`?(\w+)`?/i',
                        $statement,
                        $table
                    )
                ) {
                    continue;
                }

                $pattern = '/CONSTRAINT\s+`?(\w+)`?\s+FOREIGN\s+KEY\s*\(\s*([^)]+)\)\s*'
                    . 'REFERENCES\s+`?(\w+)`?\s*\(\s*([^)]+)\)'
                    . '((?:\s+ON\s+(?:DELETE|UPDATE)\s+'
                    . '(?:CASCADE|RESTRICT|SET\s+NULL|NO\s+ACTION))*)/i';
                if (!preg_match_all($pattern, $statement, $matches, PREG_SET_ORDER)) {
                    continue;
                }

                foreach ($matches as $match) {
                    $found[$match[1]] = [
                        'name' => $match[1],
                        'table' => $table[1],
                        'columns' => $this->identifiers($match[2]),
                        'refTable' => $match[3],
                        'refColumns' => $this->identifiers($match[4]),
                        'onDelete' => $this->action($match[5]),
                    ];
                }
            }
        }
        return $found;
    }

    /**
     * Split a column list into bare names.
     *
     * @param string $list Comma-separated, possibly backtick-quoted
     *
     * @return array<string>
     */
    private function identifiers(string $list): array
    {
        return array_map(
            static fn(string $column): string => trim(trim($column), '`'),
            explode(',', $list)
        );
    }

    /**
     * Read the ON DELETE action, defaulting the way MySQL does.
     *
     * @param string $actions The trailing ON DELETE/ON UPDATE clauses
     */
    private function action(string $actions): string
    {
        if (preg_match('/ON\s+DELETE\s+(CASCADE|RESTRICT|SET\s+NULL|NO\s+ACTION)/i', $actions, $m)) {
            return strtoupper((string) preg_replace('/\s+/', ' ', $m[1]));
        }
        return 'RESTRICT';
    }

    /**
     * The declared set, minus constraints on tables renamed away since.
     *
     * @return array<string, array{name: string, table: string, columns: array<string>,
     *     refTable: string, refColumns: array<string>, onDelete: string}>
     */
    private function currentSchemaConstraints(): array
    {
        return array_filter(
            $this->declaredInSchemaFiles(),
            static fn(array $key): bool => !in_array(
                $key['table'],
                SchemaConstraints::SUPERSEDED_TABLES,
                true
            )
        );
    }

    public function testEveryDeclaredConstraintIsListed(): void
    {
        $declared = array_keys($this->currentSchemaConstraints());
        $listed = array_column(SchemaConstraints::FOREIGN_KEYS, 'name');
        sort($declared);
        sort($listed);

        $this->assertSame(
            $declared,
            $listed,
            "SchemaConstraints::FOREIGN_KEYS no longer matches the schema files.\n"
            . "Missing from the list: " . implode(', ', array_diff($declared, $listed)) . "\n"
            . "Listed but not declared: " . implode(', ', array_diff($listed, $declared)) . "\n"
            . 'A constraint absent from the list is never repaired on installs '
            . 'that lost it, which is what #273 was about. If the table was '
            . 'renamed away, add it to SUPERSEDED_TABLES instead.'
        );
    }

    public function testEveryListedConstraintMatchesItsDefinition(): void
    {
        $declared = $this->currentSchemaConstraints();

        foreach (SchemaConstraints::FOREIGN_KEYS as $key) {
            $this->assertArrayHasKey($key['name'], $declared);
            $source = $declared[$key['name']];

            $this->assertSame($source['table'], $key['table'], "{$key['name']}: table");
            $this->assertSame($source['columns'], $key['columns'], "{$key['name']}: columns");
            $this->assertSame($source['refTable'], $key['refTable'], "{$key['name']}: refTable");
            $this->assertSame($source['refColumns'], $key['refColumns'], "{$key['name']}: refColumns");
            // ON DELETE decides whether children are cleaned up, orphaned or
            // pinned, so a mismatch here would repair the constraint into
            // different behaviour than a fresh install gets.
            $this->assertSame($source['onDelete'], $key['onDelete'], "{$key['name']}: ON DELETE");
        }
    }

    public function testSupersededTablesAreNotListed(): void
    {
        foreach (SchemaConstraints::FOREIGN_KEYS as $key) {
            $this->assertNotContains(
                $key['table'],
                SchemaConstraints::SUPERSEDED_TABLES,
                "{$key['name']} is on {$key['table']}, which was renamed away."
            );
        }
    }
}
