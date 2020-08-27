<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use ErrorException;
use Keboola\DbExtractor\Configuration\OdbcDatabaseConfig;
use Keboola\DbExtractor\OdbcDsnFactory;
use RuntimeException;

class OdbcTestConnectionFactory
{
    public static function getDbConfigArray(): array
    {
        return [
            'user' => (string) getenv('DB_USER'),
            '#password' => (string) getenv('DB_PASSWORD'),
            'host' => (string) getenv('DB_HOST'),
            'serverName' => (string) getenv('DB_SERVER_NAME'),
            'protocol' => (string) getenv('DB_PROTOCOL'),
            'dbLocale' => (string) getenv('DB_LOCALE_VALUE'),
            'port' => (string) getenv('DB_PORT'),
            'database' => (string) getenv('DB_DATABASE'),
        ];
    }

    public static function createDbConfig(): OdbcDatabaseConfig
    {
        return OdbcDatabaseConfig::fromArray(self::getDbConfigArray());
    }

    public static function createDsn(): string
    {
        $dbConfig = self::createDbConfig();
        $dsnFactory = new OdbcDsnFactory();
        return $dsnFactory->create($dbConfig);
    }

    /** @return resource */
    public static function createConnection()
    {
        $dsn = self::createDsn();
        $dbConfig = self::createDbConfig();
        $resource = @odbc_connect($dsn, $dbConfig->getUsername(), $dbConfig->getPassword());
        if ($resource === false) {
            throw new ErrorException(odbc_errormsg() . ' ' . odbc_error());
        }

        return $resource;
    }

    public static function waitForDatabase(): void
    {
        // Wait for database (port can be open and database not ready)
        $maxRetries = 60;
        $i = 0;
        echo 'boostrap.php: Waiting for database ...';
        while (true) {
            $i++;

            try {
                self::createConnection();
                echo " OK\n";
                break;
            } catch (ErrorException $e) {
                if (strpos($e->getMessage(), 'Database not found') === false &&
                    strpos($e->getMessage(), 'No connections are allowed in quiescent mode.') === false
                ) {
                    throw $e;
                }

                if ($i > $maxRetries) {
                    throw new RuntimeException('boostrap.php: Cannot connect to database: ' . $e->getMessage(), 0, $e);
                }

                echo '.';
                sleep(1);
            }
        }
    }
}
