<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Traits\Tables;

use Keboola\DbExtractor\Extractor\QuoteIdentifierTrait;

trait PkAndFkTablesTrait
{
    use QuoteIdentifierTrait;

    /** @var resource ODBC connection resource */
    protected $connection;

    public function createPkAndFkTables(
        string $table1,
        string $table1PkCol,
        string $table1FkCol,
        string $table2,
        string $table2PkCol,
        string $table2Target
    ): void {
        // Create Table 2
        $sql1 = [];
        $sql1[] = sprintf('CREATE TABLE %s (', $this->quoteIdentifier($table2));
        $sql1[] = sprintf('%s INTEGER PRIMARY KEY,', $this->quoteIdentifier($table2PkCol));
        $sql1[] = sprintf('%s INTEGER UNIQUE,', $this->quoteIdentifier($table2Target));
        $sql1[] = 'name VARCHAR(255)';
        $sql1[] = ')';
        odbc_exec($this->connection, implode(' ', $sql1));

        // Create Table 1
        $sql2 = [];
        $sql2[] = sprintf('CREATE TABLE %s (', $this->quoteIdentifier($table1));
        $sql2[] = sprintf('%s INTEGER PRIMARY KEY,', $this->quoteIdentifier($table1PkCol));
        $sql2[] = sprintf(
            '%s INTEGER REFERENCES %s (%s) CONSTRAINT table1_fk,',
            $this->quoteIdentifier($table1FkCol),
            $this->quoteIdentifier($table2),
            $this->quoteIdentifier($table2Target)
        );
        $sql2[] = 'name VARCHAR(255)';
        $sql2[] = ')';
        odbc_exec($this->connection, implode(' ', $sql2));
    }
}
