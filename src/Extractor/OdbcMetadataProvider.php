<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Generator;
use Keboola\DbExtractor\Adapter\ODBC\OdbcConnection;
use Keboola\DbExtractor\TableResultFormat\Metadata\Builder\TableBuilder;
use Keboola\DbExtractor\TableResultFormat\Metadata\Builder\MetadataBuilder;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\Table;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\TableCollection;
use Keboola\DbExtractorConfig\Configuration\ValueObject\InputTable;

class OdbcMetadataProvider implements MetadataProvider
{
    protected OdbcConnection $connection;

    /**
     * @var resource
     */
    protected $resource;

    public function __construct(OdbcConnection $connection)
    {
        $this->connection = $connection;
        $this->resource = $this->connection->getConnection();
    }

    public function getTable(InputTable $table): Table
    {
        return $this
            ->listTables([$table])
            ->getByNameAndSchema($table->getName(), $table->getSchema());
    }

    public function listTables(array $whitelist = [], bool $loadColumns = true): TableCollection
    {
        /** @var TableBuilder[] $tableBuilders */
        $tableBuilders = [];
        $primaryKeys = [];
        $builder = MetadataBuilder::create();

        // Tables
        $tables = $whitelist ? $this->queryTables($whitelist) : $this->queryAllTables();
        foreach ($tables as $table) {
            if ($table['TABLE_TYPE'] === 'TABLE' || $table['TABLE_TYPE'] === 'VIEW') {
                $schema = $table['TABLE_OWNER'] ?? 'default';
                $name = $table['TABLE_NAME'];
                $id = "$schema.$name";
                $tableBuilders[$id] = $builder
                    ->addTable()
                    ->setSchema($schema)
                    ->setName($name)
                    ->setType($table['TABLE_TYPE']);

                if ($loadColumns) {
                    $primaryKeys[$id] = iterator_to_array($this->queryPrimaryKeys($schema, $name));
                } else {
                    $tableBuilders[$id]->setColumnsNotExpected();
                }
            }
        }

        // Columns
        if ($loadColumns) {
            $columns = $whitelist ? $this->queryColumns($whitelist) : $this->queryAllColumns();
            foreach ($columns as $column) {
                $tabSchema = $column['TABLE_OWNER'] ?? 'default';
                $tabName = $column['TABLE_NAME'];
                $tabId = "$tabSchema.$tabName";
                $columnType = $column['TYPE_NAME'];
                if (isset($tableBuilders[$tabId])) {
                    $colName = $column['COLUMN_NAME'];
                    $tableBuilders[$tabId]
                        ->addColumn()
                        ->setName($colName)
                        ->setType($columnType === 'bit' ? 'bool' : $columnType)
                        ->setNullable((bool) $column['NULLABLE'])
                        ->setPrimaryKey(in_array($colName, $primaryKeys[$tabId], true));
                }
            }
        }

        return $builder->build();
    }

    private function queryAllTables(): Generator
    {
        $stmt = odbc_tables($this->resource);
        while ($table = odbc_fetch_array($stmt)) {
            yield $table;
        }
        odbc_free_result($stmt);
    }

    /**
     * @param array|InputTable[] $tables
     */
    private function queryTables(array $tables): Generator
    {
        foreach ($tables as $table) {
            $schema = $table->getSchema() === 'default' ? '' : $table->getSchema();
            $stmt = odbc_tables($this->resource, null, $schema, $table->getName());
            while ($table = odbc_fetch_array($stmt)) {
                yield $table;
            }
            odbc_free_result($stmt);
        }
    }

    private function queryPrimaryKeys(string $schema, string $table): Generator
    {
        $schema = $schema === 'default' ? '' : $schema;
        $stmt = odbc_primarykeys($this->resource, null, $schema, $table);
        while ($pk = odbc_fetch_array($stmt)) {
            yield $pk['COLUMN_NAME'];
        }
        odbc_free_result($stmt);
    }

    private function queryAllColumns(): Generator
    {
        $stmt = odbc_columns($this->resource);
        while ($column = odbc_fetch_array($stmt)) {
            yield $column;
        }
        odbc_free_result($stmt);
    }

    /**
     * @param array|InputTable[] $tables
     */
    private function queryColumns(array $tables): Generator
    {
        foreach ($tables as $table) {
            $schema = $table->getSchema() === 'default' ? '' : $table->getSchema();
            $stmt = odbc_columns($this->resource, null, $schema, $table->getName());
            while ($column = odbc_fetch_array($stmt)) {
                yield $column;
            }
            odbc_free_result($stmt);
        }
    }
}
