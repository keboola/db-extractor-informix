<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\FunctionalTests;

use Keboola\DatadirTests\DatadirTestsFromDirectoryProvider;
use Keboola\DatadirTests\DatadirTestSpecification;
use Keboola\DatadirTests\DatadirTestSpecificationInterface;
use Symfony\Component\Finder\SplFileInfo;

class DatadirTestsProvider extends DatadirTestsFromDirectoryProvider
{
    private string $testDirectory;

    /** @var DatadirTestSpecification[][] */
    protected array $datapoints;

    public function __construct(string $testDirectory = 'tests/functional')
    {
        parent::__construct($testDirectory);
        $this->testDirectory = $testDirectory;
    }

    /**
     * @return DatadirTestSpecificationInterface[][]
     */
    public function __invoke(): array
    {
        $this->datapoints = [];
        $this->processDirectory($this->testDirectory);
        return $this->datapoints;
    }

    protected function processOneTest(SplFileInfo $testSuite): void
    {
        $workingDirectory = $testSuite->getPathname();

        $name = $testSuite->getBasename();
        $sourceDatadirDirectory = $workingDirectory . '/source/data';
        $expectedStdoutFile = $workingDirectory . '/expected-stdout';
        $expectedStdout = null;
        $expectedStderrFile = $workingDirectory . '/expected-stderr';
        $expectedStderr = null;
        $expectedReturnCodeFile = $workingDirectory . '/expected-code';
        $expectedReturnCode = null;
        $expectedOutputDirectory = null;
        $outTemplateDir = $workingDirectory . '/expected/data/out';

        // Added, load stdout from file
        if (file_exists($expectedStdoutFile)) {
            $expectedStdout = (string) file_get_contents($expectedStdoutFile);
        }

        // Added, load stderr from file
        if (file_exists($expectedStderrFile)) {
            $expectedStderr = (string) file_get_contents($expectedStderrFile);
        }

        if (file_exists($expectedReturnCodeFile)) {
            $returnCode = trim((string) file_get_contents($expectedReturnCodeFile));
            if (preg_match('~^[012]$~', $returnCode)) {
                $expectedReturnCode = (int) $returnCode;
            } else {
                throw new \InvalidArgumentException(sprintf(
                    '%s: Expecting invalid return code (%s). Possible codes are: 0, 1, 2.',
                    $name,
                    $returnCode
                ));
            }
        }

        if (file_exists($outTemplateDir)) {
            if (is_null($expectedReturnCode)) {
                $expectedReturnCode = 0;
            }
            $expectedOutputDirectory = $outTemplateDir;
        }

        $this->datapoints[$name] = [
            new DatadirTestSpecification(
                $sourceDatadirDirectory,
                $expectedReturnCode,
                $expectedStdout,
                $expectedStderr,
                $expectedOutputDirectory
            ),
        ];
    }
}
