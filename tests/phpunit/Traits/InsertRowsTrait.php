<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Traits;

use Keboola\DbExtractor\Extractor\QuoteIdentifierTrait;
use Keboola\DbExtractor\Extractor\QuoteTrait;

trait InsertRowsTrait
{
    use QuoteTrait;
    use QuoteIdentifierTrait;

    /** @var resource ODBC connection resource */
    protected $connection;

    public function insertRows(string $tableName, array $columns, array $rows): void
    {
        // Generate columns statement
        $columnsSql = [];
        foreach ($columns as $name => $sqlDef) {
            $columnsSql[] = $this->quoteIdentifier($name);
        }

        // Generate values statement
        $valuesSql = [];
        foreach ($rows as $row) {
            $valuesSql[] =
                '(' .
                implode(
                    ', ',
                    array_map(fn($value) => $value === null ? 'NULL' : $this->quote((string) $value), $row)
                ) .
                ')';
        }

        // In informix cannot be multiple values in one INSERT statement
        foreach ($valuesSql as $values) {
            odbc_exec($this->connection, sprintf(
                'INSERT INTO %s (%s) VALUES %s',
                $this->quoteIdentifier($tableName),
                implode(', ', $columnsSql),
                $values
            ));
        }
    }
}
