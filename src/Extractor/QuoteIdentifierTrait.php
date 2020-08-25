<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

trait QuoteIdentifierTrait
{
    /**
     * Database specific SQL quoting for identifiers
     */
    public function quoteIdentifier(string $str): string
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }
}
