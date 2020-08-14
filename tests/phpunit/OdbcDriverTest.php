<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class OdbcDriverTest extends TestCase
{
    public function testOdbcDriverIsWorking(): void
    {
        $resource = OdbcTestConnectionFactory::create();
        Assert::assertSame(['test' => '123'], odbc_fetch_array(odbc_exec($resource, 'SELECT 123 AS test')));
    }
}
