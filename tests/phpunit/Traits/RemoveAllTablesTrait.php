<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Traits;

trait RemoveAllTablesTrait
{
    /** @var resource ODBC connection resource */
    protected $connection;

    protected function removeAllTables(): void
    {
        // Delete all tables, except sys tables
        $sql = "SELECT tabname FROM SYSTABLES WHERE tabtype IN ('T', 'E', 'V') AND tabname NOT LIKE 'sys%'";
        $stmt = odbc_exec($this->connection, $sql);
        while (true) {
            $row = odbc_fetch_array($stmt);
            if (!$row) {
                break;
            }
            odbc_exec($this->connection, sprintf('DROP TABLE %s', $row['tabname']));
        }
    }
}
