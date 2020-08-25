<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Extractor;

use Keboola\DbExtractor\Adapter\Query\DefaultQueryFactory;

/**
 * Generates SQL query when custom query is not used.
 */
class OdbcQueryFactory extends DefaultQueryFactory
{

}
