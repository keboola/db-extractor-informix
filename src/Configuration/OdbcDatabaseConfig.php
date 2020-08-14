<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Configuration;

use Keboola\DbExtractorConfig\Configuration\ValueObject\DatabaseConfig;

class OdbcDatabaseConfig extends DatabaseConfig
{
    private string $serverName;

    public static function fromArray(array $data): self
    {
        return new self(
            $data['host'],
            $data['serverName'],
            $data['port'] ? (string) $data['port'] : null,
            $data['user'],
            $data['#password'],
            $data['database'] ?? null,
            $data['schema'] ?? null
        );
    }

    public function __construct(
        string $host,
        string $serverName,
        ?string $port,
        string $username,
        string $password,
        ?string $database,
        ?string $schema
    ) {
        parent::__construct($host, $port, $username, $password, $database, $schema, null);
        $this->serverName = $serverName;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }
}
