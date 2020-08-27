<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\FunctionalTests;

use Keboola\Temp\Temp;
use PHPUnit\Framework\Assert;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use RuntimeException;
use InvalidArgumentException;
use Keboola\DbExtractor\Tests\OdbcTestConnectionFactory;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecificationInterface;
use Keboola\DatadirTests\DatadirTestsProviderInterface;
use Keboola\DbExtractor\Tests\Traits\CloseSshTunnelsTrait;
use Keboola\DbExtractor\Tests\Traits\DefaultSchemaTrait;
use Keboola\DbExtractor\Tests\Traits\GetSshKeysTrait;
use Keboola\DbExtractor\Tests\Traits\RemoveAllTablesTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class DatadirTest extends AbstractDatadirTestCase
{
    use GetSshKeysTrait;
    use CloseSshTunnelsTrait;
    use RemoveAllTablesTrait;
    use DefaultSchemaTrait;

    /** @var resource ODBC connection resource */
    protected $connection;

    protected string $testProjectDir;

    protected string $testTempDir;

    /**
     * @return resource ODBC connection resource
     */
    public function getConnection()
    {
        return $this->connection;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Test dir, eg. "/code/tests/functional/full-load-ok"
        $this->testProjectDir = $this->getTestFileDir() . '/' . $this->dataName();
        $this->testTempDir = $this->temp->getTmpFolder();

        // Create service connection
        $this->connection = OdbcTestConnectionFactory::createConnection();

        // Load setUp.php file - used to init database state
        $setUpPhpFile = $this->testProjectDir . '/setUp.php';
        if (file_exists($setUpPhpFile)) {
            // Get callback from file and check it
            $initCallback = require $setUpPhpFile;
            if (!is_callable($initCallback)) {
                throw new RuntimeException(sprintf('File "%s" must return callback!', $setUpPhpFile));
            }

            // Invoke callback
            $initCallback($this);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->closeSshTunnels();
        $this->removeAllTables();
    }

    /**
     * @return DatadirTestsProviderInterface[]
     */
    protected function getDataProviders(): array
    {
        return [
            new DatadirTestsProvider($this->getTestFileDir()),
        ];
    }

    /**
     * @dataProvider provideDatadirSpecifications
     */
    public function testDatadir(DatadirTestSpecificationInterface $specification): void
    {
        $tempDatadir = $this->getTempDatadir($specification);

        // Replace environment variables in config.json
        $configPath = $tempDatadir->getTmpFolder() . '/config.json';
        $this->replaceVariablesInFile($configPath);

        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    protected function assertMatchesSpecification(
        DatadirTestSpecificationInterface $specification,
        Process $runProcess,
        string $tempDatadir
    ): void {
        if ($specification->getExpectedReturnCode() !== null) {
            $this->assertProcessReturnCode($specification->getExpectedReturnCode(), $runProcess);
        } else {
            $this->assertNotSame(0, $runProcess->getExitCode(), 'Exit code should have been non-zero');
        }
        if ($specification->getExpectedStdout() !== null) {
            // Match format, not exact same
            $this->assertStringMatchesFormat(
                trim($specification->getExpectedStdout()),
                trim($runProcess->getOutput()),
                'Failed asserting stdout output'
            );
        }
        if ($specification->getExpectedStderr() !== null) {
            // Match format, not exact same
            $this->assertStringMatchesFormat(
                trim($specification->getExpectedStderr()),
                trim($runProcess->getErrorOutput()),
                'Failed asserting stderr output'
            );
        }
        if ($specification->getExpectedOutDirectory() !== null) {
            $this->assertDirectoryContentsSame(
                $specification->getExpectedOutDirectory(),
                $tempDatadir . '/out'
            );
        }
    }

    public function assertDirectoryContentsSame(string $expected, string $actual): void
    {
        // Copy expected dir to temp, so can be modified
        $fs = new Filesystem();
        $expectedTemp = new Temp();
        $fs->mirror($expected, $expectedTemp->getTmpFolder());

        try {
            // Manifest files generated in tests are:
            // 1. one-line JSON files
            //    -> so they are hard to read and compare in diff
            //    -> therefore they are prettified
            // 2. sometimes they contain a lot of metadata
            //    -> it is unclear
            //    -> therefore, we allow you to use wildcards instead of exact comparisons
            $this->prettifyAllManifests();
            $this->assertManifestsContentsSame($expectedTemp->getTmpFolder(), $actual);

            parent::assertDirectoryContentsSame($expectedTemp->getTmpFolder(), $actual);
        } finally {
            $expectedTemp->remove();
        }
    }

    protected function assertManifestsContentsSame(string $expectedDir, string $actualDir): void
    {
        foreach ($this->findManifests($actualDir) as $actualFile) {
            $expectedPath = str_replace($actualDir, $expectedDir, (string) $actualFile->getRealPath());
            $actualPath = (string) $actualFile->getRealPath();
            if (file_exists($expectedPath)) {
                $this->assertStringMatchesFormat(
                    trim((string) file_get_contents($expectedPath)),
                    (string) file_get_contents($actualPath),
                    $actualPath
                );

                // Files are compared and can be removed from temp dirs,
                // so they are no longer exactly matched
                unlink($expectedPath);
                unlink($actualPath);
            };
        }
    }

    protected function prettifyAllManifests(): void
    {
        foreach ($this->findManifests($this->testTempDir . '/out/tables') as $file) {
            $this->prettifyJsonFile((string) $file->getRealPath());
        }
    }

    protected function prettifyJsonFile(string $path): void
    {
        $json = (string) file_get_contents($path);
        try {
            file_put_contents($path, (string) json_encode(json_decode($json), JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
            // If a problem occurs, preserve the original contents
            file_put_contents($path, $json);
        }
    }

    protected function findManifests(string $dir): Finder
    {
        $finder = new Finder();
        return $finder->files()->in($dir)->name(['~.*\.manifest~']);
    }

    protected function replaceVariablesInFile(string $path): void
    {
        if (file_exists($path)) {
            /** @var string $config */
            $config = file_get_contents($path);
            $config = preg_replace_callback(
            // Replace eg. "${DB_SERVER_NAME}" using replaceVariableInConfig function,
            // ... so config.json in data-dir test can be static, and is processed by this regexp.
                '~"\$\{([^{}]+)\}"~',
                fn($m) => $this->getVariable($m[1]),
                $config
            );
            file_put_contents($path, $config);
        }
    }

    protected function getVariable(string $variable): string
    {
        switch ($variable) {
            case 'SSH_PUBLIC_KEY':
                $value = $this->getPublicKey();
                break;

            case 'SSH_PRIVATE_KEY':
                $value = $this->getPrivateKey();
                break;

            case 'DEFAULT_SCHEMA':
                $value = $this->getDefaultSchema();
                break;

            default:
                $value = getenv($variable);
                if ($value === false) {
                    throw new InvalidArgumentException(sprintf('Env variable "%s" not found.', $variable));
                }
                break;
        }

        /** @var string $json */
        $json = json_encode($value);
        return $json;
    }
}
