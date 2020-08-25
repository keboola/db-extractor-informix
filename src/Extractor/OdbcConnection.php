<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

class OdbcConnection extends \Keboola\DbExtractor\Adapter\ODBC\OdbcConnection
{
    use QuoteTrait;
    use QuoteIdentifierTrait;
}
