<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Throwable;
use Keboola\DbExtractor\Adapter\Exception\OdbcException;
use Keboola\DbExtractor\Adapter\ODBC\OdbcQueryResult;
use Keboola\DbExtractor\Adapter\ValueObject\QueryResult;

class OdbcConnection extends \Keboola\DbExtractor\Adapter\ODBC\OdbcConnection
{
    use QuoteTrait;
    use QuoteIdentifierTrait;

    public function testConnection(): void
    {
        // SELECT 1 is not working in some Informix databases
        $this->query("SELECT DBINFO('dbname') FROM systables WHERE tabid = 1;", 1);
    }

    protected function doQuery(string $query): QueryResult
    {
        try {
            /** @var resource|false $stmt */
            $stmt = @odbc_exec($this->connection, $query);
        } catch (Throwable $e) {
            throw new OdbcException($e->getMessage(), $e->getCode(), $e);
        }

        // "odbc_exec" can generate warning, if "set_error_handler" is not set, so we are checking it manually
        if ($stmt === false) {
            throw new OdbcException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
        }

        odbc_binmode($stmt, ODBC_BINMODE_PASSTHRU);
        odbc_longreadlen($stmt, 65536);
        return new OdbcQueryResult($stmt);
    }
}
