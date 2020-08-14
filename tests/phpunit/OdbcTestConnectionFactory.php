<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use ErrorException;
use Keboola\DbExtractor\OdbcDsnFactory;
use RuntimeException;

class OdbcTestConnectionFactory
{
    /** @return resource */
    public static function create()
    {
        $dsnFactory = new OdbcDsnFactory();
        $dsn = $dsnFactory->create(
            (string) getenv('DB_HOST'),
            (string) getenv('DB_SERVER_NAME'),
            (string) getenv('DB_PORT'),
            (string) getenv('DB_DATABASE')
        );
        $resource = @odbc_connect($dsn, (string) getenv('DB_USER'), (string) getenv('DB_PASSWORD'));
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
                self::create();
                echo " OK\n";
                break;
            } catch (ErrorException $e) {
                if (strpos($e->getMessage(), 'Database not found') === false ||
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
