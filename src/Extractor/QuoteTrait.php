<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

trait QuoteTrait
{
    public function quote(string $str): string
    {
        // Database specific SQL quoting for values
        return "'" . str_replace("'", "''", $str) . "'";
    }
}
