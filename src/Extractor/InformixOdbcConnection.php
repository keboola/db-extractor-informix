<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Keboola\DbExtractor\Adapter\Connection\DbConnection;
use Keboola\DbExtractor\Adapter\Exception\OdbcException;
use Keboola\DbExtractor\Adapter\ODBC\OdbcConnection;
use Keboola\DbExtractor\Adapter\ODBC\OdbcQueryResult;
use Psr\Log\LoggerInterface;
use Throwable;

class InformixOdbcConnection extends OdbcConnection
{
    use QuoteTrait;
    use QuoteIdentifierTrait;

    private ?int $queryTimeout;

    public function __construct(
        LoggerInterface $logger,
        string $dsn,
        string $user,
        string $password,
        ?callable $init = null,
        int $connectMaxRetries = DbConnection::CONNECT_DEFAULT_MAX_RETRIES,
        int $odbcCursorType = SQL_CURSOR_FORWARD_ONLY,
        int $odbcCursorMode = SQL_CUR_USE_DRIVER,
        ?int $queryTimeout = null
    ) {
        parent::__construct(
            $logger,
            $dsn,
            $user,
            $password,
            $init,
            $connectMaxRetries,
            $odbcCursorType,
            $odbcCursorMode
        );
        $this->queryTimeout = $queryTimeout;
    }

    public function testConnection(): void
    {
        // SELECT 1 is not working in some Informix databases
        $this->query("SELECT DBINFO('dbname') FROM systables WHERE tabid = 1;", 1);
    }

    protected function doQuery(string $query): OdbcQueryResult
    {
        try {
            /** @var resource|false $stmt */
            $stmt = odbc_prepare($this->connection, $query);
            if ($stmt === false) {
                throw new OdbcException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
            }

            if ($this->queryTimeout !== null) {
                odbc_setoption($stmt, 2, 0, $this->queryTimeout);
            }

            odbc_execute($stmt);
        } catch (Throwable $e) {
            throw new OdbcException($e->getMessage(), $e->getCode(), $e);
        }

        return new OdbcQueryResult($query, $this->getQueryMetadata($query, $stmt), $stmt);
    }

    protected function getExpectedExceptionClasses(): array
    {
        return array_merge(self::BASE_RETRIED_EXCEPTIONS, [
            OdbcException::class,
        ]);
    }
}
