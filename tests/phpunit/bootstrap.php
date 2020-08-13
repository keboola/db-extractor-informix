<?php

declare(strict_types=1);

use MyComponent\Tests\OdbcTestConnectionFactory;

require __DIR__ . '/../../vendor/autoload.php';

OdbcTestConnectionFactory::waitForDatabase();
