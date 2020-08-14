<?php

declare(strict_types=1);

use Keboola\DbExtractor\Tests\OdbcTestConnectionFactory;

require __DIR__ . '/../../vendor/autoload.php';

OdbcTestConnectionFactory::waitForDatabase();
