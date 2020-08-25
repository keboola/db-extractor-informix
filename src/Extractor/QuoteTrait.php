<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

trait QuoteTrait
{
    /**
     * Database specific SQL quoting for values
     */
    public function quote(string $str): string
    {
        return "'" . str_replace("'", "''", $str) . "'";
    }
}
