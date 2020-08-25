<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Metadata\Query;

/**
 * Wraps an SQL statement and also a callback to get the unique identifier of the object (table / column).
 */
class TableMetadataQuery
{
    private string $sql;

    /** @var callable */
    private $getUniqTableIdCallback;

    public function __construct(string $sql, callable $getUniqTableIdCallback)
    {
        $this->sql = $sql;
        $this->getUniqTableIdCallback = $getUniqTableIdCallback;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getUniqTableIdFrom(array $row): string
    {
        return (string) call_user_func($this->getUniqTableIdCallback, $row);
    }
}
