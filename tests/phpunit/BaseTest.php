<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use Keboola\DbExtractor\Tests\Traits\RemoveAllTablesTrait;
use Keboola\Temp\Temp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

abstract class BaseTest extends TestCase
{
    use RemoveAllTablesTrait;

    protected const ROOT_PATH = __DIR__ . '/../..';

    /** @var resource ODBC connection resource */
    protected $connection;

    protected Temp $temp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = OdbcTestConnectionFactory::createConnection();
        $this->temp = new Temp();
        $this->removeAllTables();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->temp->remove();
        $this->removeAllTables();
    }

    protected function createAppProcess(array $config): Process
    {
        file_put_contents($this->temp->getTmpFolder() . '/config.json', json_encode($config));
        $process = Process::fromShellCommandline(
            'php ' . self::ROOT_PATH . '/src/run.php',
            null,
            ['KBC_DATADIR' => $this->temp->getTmpFolder()]
        );
        $process->setTimeout(300);
        return $process;
    }

    protected function getConfigDbNode(): array
    {
        return OdbcTestConnectionFactory::getDbConfigArray();
    }

    protected function getConfig(): array
    {
        return [
            'parameters' => [
                'db' => $this->getConfigDbNode(),
            ],
        ];
    }
}
