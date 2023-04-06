<?php

declare(strict_types=1);

use Keboola\DbExtractor\FunctionalTests\DatabaseSetupManager;
use Keboola\DbExtractor\FunctionalTests\DatadirTest;

return function (DatadirTest $test): void {
    $manager = new DatabaseSetupManager($test->getConnection());
    $manager->createIncrementalTable();
    // no rows
};
