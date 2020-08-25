<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Metadata;

use Keboola\Datatype\Definition\Common;
use Keboola\DbExtractor\TableResultFormat\Metadata\Manifest\DefaultManifestSerializer;
use Keboola\DbExtractor\TableResultFormat\Metadata\ValueObject\Column;

/**
 * Generates a table's manifest file from the metadata objects.
 */
class OdbcManifestSerializer extends DefaultManifestSerializer
{
    protected function columnToDatatype(Column $column, array $options): Common
    {
        return new OdbcDataType($column->getType(), $options);
    }
}
