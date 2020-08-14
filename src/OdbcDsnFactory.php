<?php

declare(strict_types=1);

namespace Keboola\DbExtractor;

class OdbcDsnFactory
{
    public const ODBC_DRIVER_NAME = 'IBM Informix Informix ODBC DRIVER';

    public function create(string $host, string $serverName, string $port, string $database): string
    {
        // ServerName is additional connection parameter required by Informix
        return sprintf(
            'Driver={%s};Host=%s;Server=%s;Service=%s;Protocol=olsoctcp;Database=%s;',
            self::ODBC_DRIVER_NAME,
            $host,
            $serverName,
            $port,
            $database
        );
    }
}
