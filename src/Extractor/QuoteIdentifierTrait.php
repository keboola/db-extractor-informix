<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

trait QuoteIdentifierTrait
{
    public function quoteIdentifier(string $str): string
    {
        // Database specific SQL quoting for identifiers
        return '"' . str_replace('"', '""', $str) . '"';
    }
}
