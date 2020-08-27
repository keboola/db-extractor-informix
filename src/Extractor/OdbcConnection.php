<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

class OdbcConnection extends \Keboola\DbExtractor\Adapter\ODBC\OdbcConnection
{
    use QuoteTrait;
    use QuoteIdentifierTrait;

    public function testConnection(): void
    {
        // SELECT 1 is not working in some Informix databases
        $this->query("SELECT DBINFO('dbname') FROM systables WHERE tabid = 1;", 1);
    }
}
