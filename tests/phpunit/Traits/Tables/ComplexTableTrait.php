<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Traits\Tables;

use Keboola\DbExtractor\Tests\Traits\CreateTableTrait;
use function Keboola\Utils\sanitizeColumnName;

trait ComplexTableTrait
{
    use CreateTableTrait;

    /** @var resource ODBC connection resource */
    protected $connection;

    protected function createComplexTable(string $tableName): void
    {
        $i = 0;
        $columns = [];
        foreach (array_keys($this->getColumnsDefinitions()) as $sqlStmt) {
            $colName = strtolower(sanitizeColumnName('col_' . $i . '_' . $sqlStmt));
            $columns[$colName] = $sqlStmt;
            $i++;
        }

        $this->createTable($tableName, $columns);
    }

    /**
     * Must be modified for other databases.
     * Returns:
     * - all valid data types in database - KEY
     * - the corresponding type in the KBC metadata - VALUE
     */
    public function getColumnsDefinitions(): array
    {
        // https://www.ibm.com/support/knowledgecenter/en/SSGU8G_12.1.0/com.ibm.sqlr.doc/ids_sqr_094.htm
        return [
            // SQL definition => expected properties
            'BIGINT' => ['type' => 'BIGINT', 'nullable' => true],
            'BIGSERIAL NOT NULL' => ['type' => 'BIGSERIAL', 'nullable' => false],
            'BSON' => ['type' => 'LVARCHAR', 'length' => '4096', 'nullable' => true],
            'JSON' => ['type' => 'LVARCHAR', 'length' => '4096', 'nullable' => true],
            'BYTE' => ['type' => 'BYTE', 'nullable' => true],
            'CHAR(255)' => ['type' => 'CHAR', 'length' => '255', 'nullable' => true],
            'CHAR(255) NOT NULL' => ['type' => 'CHAR', 'length' => '255', 'nullable' => false],
            'CHARACTER(255)' => ['type' => 'CHAR', 'length' => '255', 'nullable' => true],
            'CHARACTER VARYING(255)' => ['type' => 'VARCHAR', 'length' => '255', 'nullable' => true],
            'DATE' => ['type' => 'DATE', 'nullable' => true],
            'DATETIME YEAR TO FRACTION(3)' => ['type' => 'DATETIME', 'nullable' => true],
            'DEC' => ['type' => 'DECIMAL', 'length' => '16,255', 'nullable' => true],
            'DECIMAL' => ['type' => 'DECIMAL', 'length' => '16,255', 'nullable' => true],
            'DECIMAL(20,10)' => ['type' => 'DECIMAL', 'length' => '20,10', 'nullable' => true],
            'FLOAT' => ['type' => 'FLOAT', 'nullable' => true],
            'INT8' => ['type' => 'INT8', 'nullable' => true],
            'INTEGER' => ['type' => 'INTEGER', 'nullable' => true],
            'INTERVAL DAY(3) TO HOUR' => ['type' => 'INTERVAL', 'nullable' => true],
            'MONEY' => ['type' => 'MONEY', 'length' => '16,2', 'nullable' => true],
            'NCHAR(255)' => ['type' => 'NCHAR', 'length' => '255', 'nullable' => true],
            'NUMERIC(20,10)' => ['type' => 'DECIMAL', 'length' => '20,10', 'nullable' => true],
            'NVARCHAR(255)' => ['type' => 'NVARCHAR', 'length' => '255', 'nullable' => true],
            'REAL' => ['type' => 'SMALLFLOAT', 'nullable' => true],
            'SERIAL(10) NOT NULL' => ['type' => 'SERIAL', 'nullable' => false],
            'SMALLFLOAT' => ['type' => 'SMALLFLOAT', 'nullable' => true],
            'SMALLINT' => ['type' => 'SMALLINT', 'nullable' => true],
            'TEXT' => ['type' => 'TEXT', 'nullable' => true],
            'VARCHAR(255)' => ['type' => 'VARCHAR', 'length' => '255', 'nullable' => true],
        ];
    }
}
