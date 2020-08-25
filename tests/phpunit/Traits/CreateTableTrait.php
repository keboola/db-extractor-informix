<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Traits;

use Keboola\DbExtractor\Extractor\QuoteIdentifierTrait;
use Keboola\DbExtractor\Extractor\QuoteTrait;

trait CreateTableTrait
{
    use QuoteTrait;
    use QuoteIdentifierTrait;

    /** @var resource ODBC connection resource */
    protected $connection;

    public function createTable(string $tableName, array $columns): void
    {
        // Generate columns statement
        $columnsSql = [];
        foreach ($columns as $name => $sqlDef) {
            $columnsSql[] = $this->quoteIdentifier($name) . ' ' . $sqlDef;
        }

        // Create table
        odbc_exec($this->connection, sprintf(
            'CREATE TABLE %s (%s)',
            $this->quoteIdentifier($tableName),
            implode(', ', $columnsSql)
        ));
    }
}
