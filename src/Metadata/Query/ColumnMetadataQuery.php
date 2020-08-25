<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Metadata\Query;

/**
 * Wraps an SQL statement and also a callback to get the unique identifier of the object (table / column).
 */
class ColumnMetadataQuery
{
    private string $sql;

    /** @var callable */
    private $getUniqTableIdCallback;

    /** @var callable */
    private $getUniqColumnIdCallback;

    public function __construct(string $sql, callable $getUniqTableIdCallback, callable $getUniqColumnIdCallback)
    {
        $this->sql = $sql;
        $this->getUniqTableIdCallback = $getUniqTableIdCallback;
        $this->getUniqColumnIdCallback = $getUniqColumnIdCallback;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getUniqTableIdFrom(array $row): string
    {
        return (string) call_user_func($this->getUniqTableIdCallback, $row);
    }

    public function getUniqColumnIdFrom(array $row): string
    {
        return (string) call_user_func($this->getUniqColumnIdCallback, $row);
    }
}
