<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\SyncActions;

use Keboola\Component\JsonHelper;
use Keboola\DbExtractor\Tests\BaseTest;
use Keboola\DbExtractor\Tests\Traits\DefaultSchemaTrait;
use Keboola\DbExtractor\Tests\Traits\Tables\ComplexTableTrait;
use PHPUnit\Framework\Assert;
use function Keboola\Utils\sanitizeColumnName;

class GetTablesTest extends BaseTest
{
    use ComplexTableTrait;
    use DefaultSchemaTrait;

    public function testGetTablesAllNoTable(): void
    {
        // Create config
        $config = $this->getConfig();
        $config['action'] = 'getTables';

        // Run extractor
        $process = $this->createAppProcess($config);
        $process->run();

        // Assert process state
        Assert::assertTrue($process->isSuccessful(), $process->getOutput() . $process->getErrorOutput());
        Assert::assertSame('', $process->getErrorOutput());

        // Parse output
        $tables = JsonHelper::decode($process->getOutput());
        Assert::assertSame(['tables' => [], 'status' => 'success'], $tables);
    }

    public function testGetTablesAllWithoutColumnsNotTable(): void
    {
        // Create config
        $config = $this->getConfig();
        $config['action'] = 'getTables';
        $config['parameters']['tableListFilter']['listColumns'] = false;

        // Run extractor
        $process = $this->createAppProcess($config);
        $process->run();

        // Assert process state
        Assert::assertTrue($process->isSuccessful(), $process->getOutput() . $process->getErrorOutput());
        Assert::assertSame('', $process->getErrorOutput());

        // Parse output
        $tables = JsonHelper::decode($process->getOutput());
        Assert::assertSame(['tables' => [], 'status' => 'success'], $tables);
    }

    public function testGetTablesAll(): void
    {
        // Create table
        $this->createComplexTable('complex');

        // Create config
        $config = $this->getConfig();
        $config['action'] = 'getTables';

        // Run extractor
        $process = $this->createAppProcess($config);
        $process->run();

        // Assert process state
        Assert::assertTrue($process->isSuccessful(), $process->getOutput() . $process->getErrorOutput());
        Assert::assertSame('', $process->getErrorOutput());

        // Parse output
        $output = JsonHelper::decode($process->getOutput());
        $tables = $output['tables'];

        // Assert columns separately
        $columns = $tables[0]['columns'];
        unset($tables[0]['columns']);

        Assert::assertSame('success', $output['status']);
        Assert::assertSame([['name' => 'complex', 'schema' => $this->getDefaultSchema()]], $tables);
        $this->validateColumnsMetadata($columns);
    }

    public function testGetTablesAllWithoutColumns(): void
    {
        // Create table
        $this->createComplexTable('complex');

        // Create config
        $config = $this->getConfig();
        $config['action'] = 'getTables';
        $config['parameters']['tableListFilter']['listColumns'] = false;

        // Run extractor
        $process = $this->createAppProcess($config);
        $process->run();

        // Assert process state
        Assert::assertTrue($process->isSuccessful(), $process->getOutput() . $process->getErrorOutput());
        Assert::assertSame('', $process->getErrorOutput());

        // Parse output
        $output = JsonHelper::decode($process->getOutput());
        $tables = $output['tables'];

        // Assert no columns
        Assert::assertSame('success', $output['status']);
        Assert::assertSame([['name' => 'complex', 'schema' => $this->getDefaultSchema()]], $tables);
    }

    public function testGetTablesWhiteList(): void
    {
        // Create table
        $this->createComplexTable('complex1');
        $this->createComplexTable('complex2');
        $this->createComplexTable('complex3');

        // Create config
        $config = $this->getConfig();
        $config['action'] = 'getTables';
        $config['parameters']['tableListFilter']['tablesToList'] = [
          [
              'tableName' => 'complex2',
              'schema' => $this->getDefaultSchema(),
          ],
        ];

        // Run extractor
        $process = $this->createAppProcess($config);
        $process->run();

        // Assert process state
        Assert::assertTrue($process->isSuccessful(), $process->getOutput() . $process->getErrorOutput());
        Assert::assertSame('', $process->getErrorOutput());

        // Parse output
        $output = JsonHelper::decode($process->getOutput());
        $tables = $output['tables'];

        // Assert columns separately
        $columns = $tables[0]['columns'];
        unset($tables[0]['columns']);

        Assert::assertSame('success', $output['status']);
        Assert::assertSame([['name' => 'complex2', 'schema' => $this->getDefaultSchema()]], $tables);
        $this->validateColumnsMetadata($columns);
    }

    public function testGetTablesWhiteListNoColumns(): void
    {
        // Create table
        $this->createComplexTable('complex1');
        $this->createComplexTable('complex2');
        $this->createComplexTable('complex3');

        // Create config
        $config = $this->getConfig();
        $config['action'] = 'getTables';
        $config['parameters']['tableListFilter']['listColumns'] = false;
        $config['parameters']['tableListFilter']['tablesToList'] = [
            [
                'tableName' => 'complex2',
                'schema' => $this->getDefaultSchema(),
            ],
        ];

        // Run extractor
        $process = $this->createAppProcess($config);
        $process->run();

        // Assert process state
        Assert::assertTrue($process->isSuccessful(), $process->getOutput() . $process->getErrorOutput());
        Assert::assertSame('', $process->getErrorOutput());

        // Parse output
        $output = JsonHelper::decode($process->getOutput());
        $tables = $output['tables'];

        // Assert no columns
        Assert::assertSame('success', $output['status']);
        Assert::assertSame([['name' => 'complex2', 'schema' => $this->getDefaultSchema()]], $tables);
    }

    protected function validateColumnsMetadata(array $columns): void
    {
        $i = 0;
        foreach ($this->getColumnsDefinitions() as $sqlStmt => $expected) {
            // Column names are case-insensitive, so strtolower is needed.
            $expectedColName = strtolower(sanitizeColumnName('col_' . $i . '_' . $sqlStmt));

            // Assert index
            $column = $columns[$i] ?? null;
            Assert::assertNotNull(
                $column,
                sprintf('Column "%s" not found at index "%s".', $expectedColName, $i)
            );

            // Assert name
            Assert::assertSame($expectedColName, $column['name']);

            // Assert type
            Assert::assertSame(
                $expected['type'],
                $column['type'],
                sprintf('Unexpected type for column "%s".', $expectedColName)
            );

            $i++;
        }
    }
}
