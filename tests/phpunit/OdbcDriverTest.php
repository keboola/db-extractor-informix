<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use PHPUnit\Framework\Assert;

class OdbcDriverTest extends BaseTest
{
    public function testOdbcDriverIsWorking(): void
    {
        Assert::assertSame(
            ['test' => '123'],
            odbc_fetch_array(odbc_exec($this->connection, 'SELECT 123 AS test'))
        );
    }
}
