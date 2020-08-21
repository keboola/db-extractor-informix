<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

class OdbcConnection extends \Keboola\DbExtractor\Adapter\ODBC\OdbcConnection
{
    public function quote(string $str): string
    {
        // Database specific SQL quoting for values
        return "'" . str_replace("'", "''", $str) . "'";
    }

    public function quoteIdentifier(string $str): string
    {
        // Database specific SQL quoting for identifiers
        return '`' . str_replace('`', '``', $str) . '`';
    }
}
