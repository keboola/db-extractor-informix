<?php

declare(strict_types=1);

use Keboola\DbExtractor\FunctionalTests\DatadirTest;
use Keboola\DbExtractor\FunctionalTests\DatabaseSetupManager;

return function (DatadirTest $test): void {
    $manager = new DatabaseSetupManager($test->getConnection());
    $manager->createSimpleTable();
    $manager->generateSimpleTableRows();
};
