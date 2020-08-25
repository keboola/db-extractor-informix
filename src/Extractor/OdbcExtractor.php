<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use InvalidArgumentException;
use Keboola\DbExtractor\Adapter\ExportAdapter;
use Keboola\DbExtractor\Adapter\ODBC\OdbcExportAdapter;
use Keboola\DbExtractor\Configuration\OdbcDatabaseConfig;
use Keboola\DbExtractor\Exception\UserException;
use Keboola\DbExtractor\Metadata\OdbcMetadataProviderFactory;
use Keboola\DbExtractor\OdbcDsnFactory;
use Keboola\DbExtractor\TableResultFormat\Exception\ColumnNotFoundException;
use Keboola\DbExtractorConfig\Configuration\ValueObject\DatabaseConfig;
use Keboola\DbExtractorConfig\Configuration\ValueObject\ExportConfig;

class OdbcExtractor extends BaseExtractor
{
    protected OdbcConnection $connection;

    public function testConnection(): void
    {
        $this->connection->testConnection();
    }

    protected function createConnection(DatabaseConfig $databaseConfig): void
    {
        if (!$databaseConfig instanceof OdbcDatabaseConfig) {
            throw new InvalidArgumentException('Expected OdbcDatabaseConfig.');
        }

        $dsnFactory = new OdbcDsnFactory();
        $dsn = $dsnFactory->create(
            $databaseConfig->getHost(),
            $databaseConfig->getServerName(),
            $databaseConfig->getPort(),
            $databaseConfig->getDatabase(),
        );
        $this->connection = new OdbcConnection(
            $this->logger,
            $dsn,
            $databaseConfig->getUsername(),
            $databaseConfig->getPassword()
        );
    }

    protected function createExportAdapter(): ExportAdapter
    {
        $queryFactory = new OdbcQueryFactory($this->state);
        return new OdbcExportAdapter(
            $this->logger,
            $this->connection,
            $queryFactory,
            $this->dataDir,
            $this->state
        );
    }

    protected function getMetadataProvider(): MetadataProvider
    {
        $factory = new OdbcMetadataProviderFactory($this->connection, $this->getDatabaseConfig());
        return $factory->create();
    }

    protected function validateIncrementalFetching(ExportConfig $exportConfig): void
    {
        // Check that incremental fetching column exists
        try {
            $this
                ->getMetadataProvider()
                ->getTable($exportConfig->getTable())
                ->getColumns()
                ->getByName($exportConfig->getIncrementalFetchingColumn());
        } catch (ColumnNotFoundException $e) {
            throw new UserException(
                sprintf(
                    'Column "%s" specified for incremental fetching was not found in the table.',
                    $exportConfig->getIncrementalFetchingColumn()
                )
            );
        }

        // Incremental loading should work with all available types.
        // If you need to restrict it, then validation should be added HERE.
    }

    protected function getMaxOfIncrementalFetchingColumn(ExportConfig $exportConfig): ?string
    {
        $sql = sprintf(
            'SELECT MAX(%s) as %s FROM %s.%s',
            $this->connection->quoteIdentifier($exportConfig->getIncrementalFetchingColumn()),
            $this->connection->quoteIdentifier($exportConfig->getIncrementalFetchingColumn()),
            $this->connection->quoteIdentifier($exportConfig->getTable()->getSchema()),
            $this->connection->quoteIdentifier($exportConfig->getTable()->getName())
        );
        $result = $this->connection->query($sql, $exportConfig->getMaxRetries())->fetchAll();
        return $result ? $result[0][$exportConfig->getIncrementalFetchingColumn()] : null;
    }


    protected function createDatabaseConfig(array $data): DatabaseConfig
    {
        return OdbcDatabaseConfig::fromArray($data);
    }
}
