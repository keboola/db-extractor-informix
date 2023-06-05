<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Metadata;

use Keboola\Component\UserException;
use Keboola\DbExtractor\Adapter\Connection\DbConnection;
use Keboola\DbExtractor\Adapter\Metadata\MetadataProvider;
use Keboola\DbExtractor\Adapter\ValueObject\QueryResult;
use Keboola\DbExtractor\Metadata\Query\MetadataQueryFactory;
use Keboola\DbExtractor\TableResultFormat\Metadata\Builder\ColumnBuilder;
use Keboola\DbExtractor\TableResultFormat\Metadata\Builder\MetadataBuilder;
use Keboola\DbExtractor\TableResultFormat\Metadata\Builder\TableBuilder;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\Table;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\TableCollection;
use Keboola\DbExtractorConfig\Configuration\ValueObject\InputTable;

class OdbcMetadataProvider implements MetadataProvider
{
    public const METADATA_QUERY_RETRIES = 3;

    protected DbConnection $connection;

    protected MetadataQueryFactory $queryFactory;

    protected OdbcMetadataProcessor $processor;

    /** @var TableCollection[] */
    private array $cache = [];

    public function __construct(
        DbConnection $connection,
        MetadataQueryFactory $queryFactory,
        OdbcMetadataProcessor $processor
    ) {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
        $this->processor = $processor;
    }

    public function getTable(InputTable $table): Table
    {
        return $this
            ->listTables([$table])
            ->getByNameAndSchema($table->getName(), $table->getSchema());
    }

    /**
     * @param array|InputTable[] $whitelist
     * @param bool $loadColumns if false, columns metadata are NOT loaded, useful if there are a lot of tables
     */
    public function listTables(array $whitelist = [], bool $loadColumns = true): TableCollection
    {
        // Return cached value if present
        $cacheKey = md5(serialize(func_get_args()));
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $builder = MetadataBuilder::create();

        /** @var TableBuilder[] $tableBuilders */
        $tableBuilders = [];

        /** @var ColumnBuilder[] $columnBuilders */
        $columnBuilders = [];

        // Load tables
        $this->loadTables($whitelist, $loadColumns, $builder, $tableBuilders);

        // Load columns and constraints, if enabled, and some tables are present
        /** @var string[] $tableIds */
        $tableIds = array_keys($tableBuilders);
        if ($loadColumns && $tableIds) {
            $this->loadColumns($whitelist, $tableIds, $tableBuilders, $columnBuilders);
            $this->loadConstraints($whitelist, $tableIds, $columnBuilders);
        }

        // Build final metadata
        return $builder->build();
    }

    protected function loadTables(
        array $whitelist,
        bool $loadColumns,
        MetadataBuilder $builder,
        array &$tableBuilders
    ): void {
        $tablesQuery = $this->queryFactory->createTablesQuery($whitelist);
        foreach ($this->query($tablesQuery->getSql())->getIterator() as $tableResult) {
            $tableBuilder = $builder->addTable();
            $tableId = $tablesQuery->getUniqTableIdFrom($tableResult);
            $tableBuilders[$tableId] = $tableBuilder;
            if (!$loadColumns) {
                $tableBuilder->setColumnsNotExpected();
            }

            // Copy values from result to table builder
            $this->processor->processTableMetadata($tableBuilder, $tableResult);
        }
    }

    protected function loadColumns(
        array $whitelist,
        array $tableIds,
        array &$tableBuilders,
        array &$columnBuilders
    ): void {
        $columnsQuery = $this->queryFactory->createColumnsQuery($whitelist, $tableIds);
        foreach ($this->query($columnsQuery->getSql())->getIterator() as $columnResult) {
            $tableId = $columnsQuery->getUniqTableIdFrom($columnResult);
            if (!isset($tableBuilders[$tableId])) {
                // Table is present in columns list but not in tables list -> skip
                continue;
            }

            $tableBuilder = $tableBuilders[$tableId];
            $columnId = $columnsQuery->getUniqColumnIdFrom($columnResult);

            // If a join is used in SQL, then one column can occur in results more than once.
            if (isset($columnBuilders[$columnId])) {
                $columnBuilder = $columnBuilders[$columnId];
            } else {
                $columnBuilder = $tableBuilder->addColumn();
                $columnBuilders[$columnId] = $columnBuilder;
            }

            if (!isset($columnResult['colname'])) {
                throw new UserException(sprintf(
                    'Cannot retrieve all column metadata via query "%s". Result: %s',
                    $columnsQuery->getSql(),
                    var_export($columnResult, true)
                ));
            }

            // Copy values from result to column builder
            $this->processor->processColumnMetadata($columnBuilder, $columnResult);
        }
    }

    protected function loadConstraints(
        array $whitelist,
        array $tableIds,
        array &$columnBuilders
    ): void {
        $constraintsQuery = $this->queryFactory->createColumnsConstraintsQuery($whitelist, $tableIds);
        foreach ($this->query($constraintsQuery->getSql())->getIterator() as $constraintResult) {
            $columnId = $constraintsQuery->getUniqColumnIdFrom($constraintResult);
            if (!isset($columnBuilders[$columnId])) {
                // Column is present in constraints but not in columns list -> skip
                continue;
            }

            // Copy values from result to column builder
            $columnBuilder = $columnBuilders[$columnId];
            $this->processor->processColumnConstraintsMetadata($columnBuilder, $constraintResult);
        }
    }

    protected function query(string $sql): QueryResult
    {
        return $this->connection->query($sql, self::METADATA_QUERY_RETRIES);
    }
}
