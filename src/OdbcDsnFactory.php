<?php

declare(strict_types=1);

namespace Keboola\DbExtractor;

use Keboola\DbExtractor\Configuration\OdbcDatabaseConfig;

class OdbcDsnFactory
{
    public const ODBC_DRIVER_NAME = 'IBM Informix Informix ODBC DRIVER';

    public function create(OdbcDatabaseConfig $dbConfig): string
    {
        // ServerName is additional connection parameter required by Informix
        $dsn = sprintf(
            'Driver={%s};Host=%s;Server=%s;Service=%s;Database=%s;Protocol=%s;DB_LOCALE=%s;CLIENT_LOCALE=%s;',
            self::ODBC_DRIVER_NAME,
            $dbConfig->getHost(),
            $dbConfig->getServerName(),
            $dbConfig->getPort(),
            $dbConfig->getDatabase(),
            $dbConfig->getProtocol(),
            $dbConfig->getDbLocale(),
            // Solution of "character conversion error":
            // specify explicitly for Client Locale the same locate as for Database Locale
            $dbConfig->getDbLocale()
        );

        // Enable double-quotes as escape character
        $dsn .= 'DELIMIDENT=y;';

        // PHP is single threaded, it should be faster
        // https://www.ibm.com/support/knowledgecenter/SSGU8G_12.1.0/com.ibm.odbc.doc/ids_odbc_046.htm
        $dsn .= 'SINGLETHREADED=1;';

        return $dsn;
    }
}
