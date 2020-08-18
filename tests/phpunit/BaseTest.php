<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use Keboola\Temp\Temp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

abstract class BaseTest extends TestCase
{
    protected const ROOT_PATH = __DIR__ . '/../..';

    /** @var resource */
    protected $connection;

    protected Temp $temp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = OdbcTestConnectionFactory::create();
        $this->temp = new Temp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->temp->remove();
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
        return [
            'host' => (string) getenv('DB_HOST'),
            'serverName' => (string) getenv('DB_SERVER_NAME'),
            'port' => (string) getenv('DB_PORT'),
            'database' => (string) getenv('DB_DATABASE'),
            'user' => (string) getenv('DB_USER'),
            '#password' => (string) getenv('DB_PASSWORD'),
        ];
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