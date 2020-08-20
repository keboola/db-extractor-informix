<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Metadata;

use Keboola\DbExtractor\Adapter\Connection\DbConnection;
use Keboola\DbExtractor\Metadata\Query\MetadataQueryFactory;
use Keboola\DbExtractorConfig\Configuration\ValueObject\DatabaseConfig;

class OdbcMetadataProviderFactory
{
    private DbConnection $connection;

    private DatabaseConfig $dbConfig;

    public function __construct(DbConnection $connection, DatabaseConfig $dbConfig)
    {
        $this->connection = $connection;
        $this->dbConfig = $dbConfig;
    }

    public function create(): OdbcMetadataProvider
    {
        return new OdbcMetadataProvider(
            $this->connection,
            new MetadataQueryFactory($this->connection, $this->dbConfig),
            new MetadataProcessor($this->dbConfig)
        );
    }
}
