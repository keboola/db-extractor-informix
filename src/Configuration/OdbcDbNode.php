<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Configuration;

use Keboola\DbExtractorConfig\Configuration\NodeDefinition\DbNode;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Definition of the "db" configuration node.
 */
class OdbcDbNode extends DbNode
{
    protected function init(NodeBuilder $builder): void
    {
        parent::init($builder);
        $this->addServerNameNode($builder);
    }

    protected function addServerNameNode(NodeBuilder $builder): void
    {
        // ServerName is additional connection parameter required by Informix
        // https://www.ibm.com/support/knowledgecenter/en/SSGU8G_12.1.0/com.ibm.adref.doc/ids_adr_0045.htm
        $builder->scalarNode('serverName')->isRequired();
    }

    protected function addHostNode(NodeBuilder $builder): void
    {
        $builder->scalarNode('host')->isRequired();
    }

    protected function addDatabaseNode(NodeBuilder $builder): void
    {
        $builder->scalarNode('database')->cannotBeEmpty()->isRequired();
    }

    protected function addPortNode(NodeBuilder $builder): void
    {
        $builder->scalarNode('port')->isRequired();
    }
}
