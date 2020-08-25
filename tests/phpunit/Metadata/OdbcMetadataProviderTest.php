<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Metadata;

use Keboola\DbExtractor\Extractor\MetadataProvider;
use Keboola\DbExtractor\Extractor\OdbcConnection;
use Keboola\DbExtractor\Metadata\OdbcMetadataProviderFactory;
use Keboola\DbExtractor\OdbcDsnFactory;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\ColumnCollection;
use Keboola\DbExtractor\Tests\BaseTest;
use Keboola\DbExtractor\Tests\Traits\DefaultSchemaTrait;
use Keboola\DbExtractor\Tests\Traits\Tables\ComplexTableTrait;
use Keboola\DbExtractor\Tests\Traits\Tables\PkAndFkTablesTrait;
use Keboola\DbExtractorConfig\Configuration\ValueObject\DatabaseConfig;
use Keboola\DbExtractorConfig\Configuration\ValueObject\InputTable;
use PHPUnit\Framework\Assert;
use Psr\Log\NullLogger;
use function Keboola\Utils\sanitizeColumnName;

class OdbcMetadataProviderTest extends BaseTest
{
    use ComplexTableTrait;
    use PkAndFkTablesTrait;
    use DefaultSchemaTrait;

    private MetadataProvider $metadataProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $logger = new NullLogger();
        $dsnFactory = new OdbcDsnFactory();
        $dsn = $dsnFactory->create(
            (string) getenv('DB_HOST'),
            (string) getenv('DB_SERVER_NAME'),
            (string) getenv('DB_PORT'),
            (string) getenv('DB_DATABASE')
        );
        $connection = new OdbcConnection(
            $logger,
            $dsn,
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASSWORD')
        );

        $metadataProviderFactory = new OdbcMetadataProviderFactory(
            $connection,
            DatabaseConfig::fromArray($this->getConfigDbNode())
        );
        $this->metadataProvider = $metadataProviderFactory->create();
    }

    public function testListAllTablesNoTable(): void
    {
        $collection = $this->metadataProvider->listTables();
        Assert::assertTrue($collection->isEmpty());
    }

    public function testListAllTables(): void
    {
        // Create table
        $this->createComplexTable('complex');
        $collection = $this->metadataProvider->listTables();
        Assert::assertFalse($collection->isEmpty());

        // Assert table
        $tables = $collection->getAll();
        $table = $tables[0];
        Assert::assertCount(1, $tables);
        Assert::assertSame('complex', $table->getName());
        Assert::assertSame('table', $table->getType());

        // Assert columns
        $columns = $table->getColumns();
        Assert::assertFalse($columns->isEmpty());
        $this->validateColumnsMetadata($columns);
    }

    public function testListAllTablesNoColumns(): void
    {
        // Create tables
        $this->createComplexTable('complex1');
        $this->createComplexTable('complex2');
        $collection = $this->metadataProvider->listTables([], false);
        Assert::assertFalse($collection->isEmpty());

        // Assert no columns
        $tables = $collection->getAll();
        Assert::assertCount(2, $tables);
        Assert::assertFalse($tables[0]->hasColumns());
        Assert::assertFalse($tables[1]->hasColumns());
    }

    public function testListTablesWhitelist(): void
    {
        // Create tables
        $this->createComplexTable('complex1');
        $this->createComplexTable('complex2');
        $this->createComplexTable('complex3');
        $collection = $this->metadataProvider->listTables(
            [new InputTable('complex2', $this->getDefaultSchema())],
            true
        );
        Assert::assertFalse($collection->isEmpty());

        // Assert table
        $tables = $collection->getAll();
        $table = $tables[0];
        Assert::assertCount(1, $tables);
        Assert::assertSame('complex2', $table->getName());
        Assert::assertSame('table', $table->getType());

        // Assert columns
        $columns = $table->getColumns();
        Assert::assertFalse($columns->isEmpty());
        $this->validateColumnsMetadata($columns);
    }

    public function testListTablesWhitelistNoColumn(): void
    {
        // Create tables
        $this->createComplexTable('complex1');
        $this->createComplexTable('complex2');
        $this->createComplexTable('complex3');
        $collection = $this->metadataProvider->listTables(
            [new InputTable('complex2', $this->getDefaultSchema())],
            false
        );
        Assert::assertFalse($collection->isEmpty());

        // Assert table
        $tables = $collection->getAll();
        $table = $tables[0];
        Assert::assertCount(1, $tables);
        Assert::assertSame('complex2', $table->getName());
        Assert::assertSame('table', $table->getType());

        // Assert columns
        Assert::assertFalse($table->hasColumns());
    }

    public function testConstraints(): void
    {
        // Create tables
        $this->createPkAndFkTables(
            'table1',
            'id1',
            'fk1',
            'table2',
            'id2',
            'fk1_target'
        );

        $collection = $this->metadataProvider->listTables();
        Assert::assertFalse($collection->isEmpty());

        // Assert table
        $tables = $collection->getAll();
        Assert::assertCount(2, $tables);
        Assert::assertSame('table1', $tables[0]->getName());
        Assert::assertSame('table', $tables[0]->getType());
        Assert::assertSame('table2', $tables[1]->getName());
        Assert::assertSame('table', $tables[1]->getType());

        $id1Col = $tables[0]->getColumns()->getByName('id1');
        $fk1Col = $tables[0]->getColumns()->getByName('fk1');
        $id2Col = $tables[1]->getColumns()->getByName('id2');
        $fk1TargetCol = $tables[1]->getColumns()->getByName('fk1_target');

        Assert::assertTrue($id1Col->isPrimaryKey());
        Assert::assertTrue($id2Col->isPrimaryKey());
        Assert::assertFalse($fk1Col->isPrimaryKey());
        Assert::assertFalse($fk1TargetCol->isPrimaryKey());

        Assert::assertFalse($id1Col->hasForeignKey());
        Assert::assertFalse($id2Col->hasForeignKey());
        Assert::assertTrue($fk1Col->hasForeignKey());
        Assert::assertFalse($fk1TargetCol->hasForeignKey());

        $fk1Fk = $fk1Col->getForeignKey();
        Assert::assertSame('fk1_target', $fk1Fk->getRefColumn());
        Assert::assertSame($this->getDefaultSchema(), $fk1Fk->getRefSchema());
        Assert::assertSame('table2', $fk1Fk->getRefTable());
    }


    protected function validateColumnsMetadata(ColumnCollection $columns): void
    {
        $i = 0;
        foreach ($this->getColumnsDefinitions() as $sqlStmt => $expected) {
            // Column names are case-insensitive, so strtolower is needed.
            $expectedColName = strtolower(sanitizeColumnName('col_' . $i . '_' . $sqlStmt));

            // Get column (if not exists, exception is thrown)
            $column = $columns->getByName($expectedColName);
            Assert::assertSame($expectedColName, $column->getName());
            Assert::assertSame($expected['type'], $column->getType());
            Assert::assertSame(
                $expected['nullable'],
                $column->isNullable(),
                sprintf('Unexpected nullable for column "%s".', $expectedColName)
            );

            if ($column->hasLength()) {
                Assert::assertSame(
                    $expected['length'] ?? null,
                    $column->getLength(),
                    sprintf('Unexpected length for column "%s".', $expectedColName)
                );
            } elseif (isset($expected['length'])) {
                Assert::fail(
                    sprintf('No length set for column "%s", expected "%s".', $expectedColName, $expected['length'])
                );
            }

            $i++;
        }
    }
}
