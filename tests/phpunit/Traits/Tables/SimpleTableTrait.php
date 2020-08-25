<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Traits\Tables;

use Keboola\DbExtractor\Tests\Traits\CreateTableTrait;
use Keboola\DbExtractor\Tests\Traits\InsertRowsTrait;

trait SimpleTableTrait
{
    use CreateTableTrait;
    use InsertRowsTrait;

    /** @var resource ODBC connection resource */
    protected $connection;

    public function createSimpleTable(string $tableName = 'simple'): void
    {
        $this->createTable($tableName, $this->getSimpleTableColumns());
    }

    public function generateSimpleTableRows(string $tableName = 'simple'): void
    {
        $this->insertRows($tableName, $this->getSimpleTableColumns(), $this->getSimpleTableRows());
    }

    protected function getSimpleTableRows(): array
    {
        return [
            [1, 'Jack Dawson', '2020-10-03 01:02:34'],
            [2, 'Xander Thomas', null],
            [3, 'Jay Macdonald', '2020-10-01 10:20:30'],
        ];
    }


    protected function getSimpleTableColumns(): array
    {
        return [
            'id' => 'INTEGER PRIMARY KEY', // primary key
            'name' => 'NVARCHAR(255) NOT NULL', // not null
            'date' => 'DATETIME YEAR TO FRACTION DEFAULT NULL', // nullable
        ];
    }
}
