<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Configuration;

use Keboola\DbExtractorConfig\Configuration\ValueObject\DatabaseConfig;

/**
 * Value object that contains the configuration from the "db" node.
 */
class OdbcDatabaseConfig extends DatabaseConfig
{
    public const PROTOCOL_ONSOCTCP = 'onsoctcp';
    public const PROTOCOL_ONSOCSSL = 'onsocssl';

    private string $serverName;

    private string $protocol;

    private string $dbLocale;

    private ?int $queryTimeout;

    public static function fromArray(array $data): self
    {
        return new self(
            $data['host'],
            $data['serverName'],
            $data['protocol'],
            $data['dbLocale'],
            $data['port'] ? (string) $data['port'] : null,
            $data['user'],
            $data['#password'],
            $data['database'] ?? null,
            $data['schema'] ?? null,
            $data['queryTimeout'] ?? null
        );
    }

    public function __construct(
        string $host,
        string $serverName,
        string $protocol,
        string $dbLocale,
        ?string $port,
        string $username,
        string $password,
        ?string $database,
        ?string $schema,
        ?int $queryTimeout
    ) {
        parent::__construct($host, $port, $username, $password, $database, $schema, null);
        $this->serverName = $serverName;
        $this->protocol = $protocol;
        $this->dbLocale = $dbLocale;
        $this->queryTimeout = $queryTimeout;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getDbLocale(): string
    {
        return $this->dbLocale;
    }

    public function getQueryTimeout(): ?int
    {
        return $this->queryTimeout;
    }
}
