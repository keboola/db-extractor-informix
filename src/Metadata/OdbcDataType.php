<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Metadata;

use Keboola\Datatype\Definition\GenericStorage;

/**
 * Converts the column's data type from the database
 * to the data type used in KBC, see: https://help.keboola.com/storage/tables/
 *
 * Often it is enough to use GenericStorage.
 * If necessary, the implementation goes to this class.
 */
class OdbcDataType extends GenericStorage
{

}
