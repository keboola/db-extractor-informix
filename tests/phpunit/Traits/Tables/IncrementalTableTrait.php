<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Traits\Tables;

use Keboola\DbExtractor\Tests\Traits\CreateTableTrait;
use Keboola\DbExtractor\Tests\Traits\InsertRowsTrait;

trait IncrementalTableTrait
{
    use CreateTableTrait;
    use InsertRowsTrait;

    /** @var resource ODBC connection resource */
    protected $connection;

    public function createIncrementalTable(string $tableName = 'incremental'): void
    {
        $this->createTable($tableName, $this->getIncrementalTableColumns());
    }

    public function generateIncrementalTableRows(string $tableName = 'incremental'): void
    {
        $this->insertRows($tableName, $this->getIncrementalTableColumns(), $this->getIncrementalTableRows());
    }

    protected function getIncrementalTableRows(): array
    {
        return [
            [1, 80, 10.1, 'bBC', '10/03/2020', '2020-10-01 10:00:00'],
            [2, 70, 50.2, '3BC', '10/02/2020', '2020-10-03 10:00:00'],
            [3, 60, 20.3, 'aBC', '10/01/2020', '2020-10-02 10:00:00'],
            [4, 50, 80.4, '1BC', '10/05/2020', '2020-10-08 10:00:00'],
            [5, 40, 60.5, 'cBC', '10/06/2020', '2020-10-04 10:00:00'],
            [6, 30, 30.6, '2BC', '10/04/2020', '2020-10-07 10:00:00'],
            [7, 20, 40.7, 'dBC', '10/08/2020', '2020-10-06 10:00:00'],
            [8, 10, 70.8, 'eBC', '10/07/2020', '2020-10-05 10:00:00'],
        ];
    }


    protected function getIncrementalTableColumns(): array
    {
        return [
            'id' => 'INTEGER PRIMARY KEY', // primary key
            // all nullables:
            'int' => 'INTEGER DEFAULT NULL',
            'decimal' => 'DECIMAL DEFAULT NULL',
            'text' => 'NVARCHAR(255) DEFAULT NULL',
            'date' => 'DATE DEFAULT NULL',
            'datetime' => 'DATETIME YEAR TO FRACTION DEFAULT NULL',
        ];
    }
}
