<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Traits;

trait DefaultSchemaTrait
{
    protected function getDefaultSchema(): string
    {
        // In informix schema = owner
        return (string) getenv('DB_USER');
    }
}
