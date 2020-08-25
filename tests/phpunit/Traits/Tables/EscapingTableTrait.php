<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Traits\Tables;

use Keboola\DbExtractor\Tests\Traits\CreateTableTrait;
use Keboola\DbExtractor\Tests\Traits\InsertRowsTrait;

trait EscapingTableTrait
{
    use CreateTableTrait;
    use InsertRowsTrait;

    /** @var resource ODBC connection resource */
    protected $connection;

    public function createEscapingTable(string $tableName = 'escaping'): void
    {
        $this->createTable($tableName, $this->getEscapingTableColumns());
    }

    public function generateEscapingTableRows(string $tableName = 'escaping'): void
    {
        $this->insertRows($tableName, $this->getEscapingTableColumns(), $this->getEscapingTableRows());
    }

    protected function getEscapingTableRows(): array
    {
        return [
            [1, '"""'],
            [2, '\'\'\''],
            [3, '```'],
            [4, '1234úěščřžýáíé+!@#$%^&*()'],
            [5, "\ta\nb\r\nc\td"],
        ];
    }


    protected function getEscapingTableColumns(): array
    {
        return [
            'id' => 'INTEGER PRIMARY KEY', // primary key
            'value' => 'NVARCHAR(255) DEFAULT NULL', // nullable
        ];
    }
}
