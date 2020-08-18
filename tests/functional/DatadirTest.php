<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\FunctionalTests;

use InvalidArgumentException;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecificationInterface;
use Keboola\DatadirTests\DatadirTestsProviderInterface;
use Keboola\DbExtractor\Tests\Traits\CloseSshTunnelsTrait;
use Keboola\DbExtractor\Tests\Traits\GetSshKeysTrait;
use Symfony\Component\Process\Process;

class DatadirTest extends AbstractDatadirTestCase
{
    use GetSshKeysTrait;
    use CloseSshTunnelsTrait;

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->closeSshTunnels();
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
        if (file_exists($configPath)) {
            /** @var string $config */
            $config = file_get_contents($configPath);
            $config = preg_replace_callback(
                // Replace eg. "${DB_SERVER_NAME}" using replaceVariableInConfig function,
                // ... so config.json in data-dir test can be static, and is processed by this regexp.
                '~"\$\{([^{}]+)\}"~',
                fn($m) => $this->replaceVariableInConfig($m[1]),
                $config
            );
            file_put_contents($configPath, $config);
        }

        $process = $this->runScript($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    protected function replaceVariableInConfig(string $variable): string
    {
        switch ($variable) {
            case 'SSH_PUBLIC_KEY':
                $value = $this->getPublicKey();
                break;

            case 'SSH_PRIVATE_KEY':
                $value = $this->getPrivateKey();
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
}
