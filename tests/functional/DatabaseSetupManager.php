<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\FunctionalTests;

use Keboola\DbExtractor\Tests\Traits\Tables\EscapingTableTrait;
use Keboola\DbExtractor\Tests\Traits\Tables\IncrementalTableTrait;
use Keboola\DbExtractor\Tests\Traits\Tables\SimpleTableTrait;

class DatabaseSetupManager
{
    use SimpleTableTrait;
    use EscapingTableTrait;
    use IncrementalTableTrait;

    /** @var resource ODBC connection resource */
    protected $connection;

    /**
     * @param resource $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }
}
