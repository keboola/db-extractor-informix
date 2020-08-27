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
        $this->addProtocol($builder);
        $this->addDbLocale($builder);
    }

    protected function addServerNameNode(NodeBuilder $builder): void
    {
        // ServerName is additional connection parameter required by Informix
        // https://www.ibm.com/support/knowledgecenter/en/SSGU8G_12.1.0/com.ibm.adref.doc/ids_adr_0045.htm
        $builder->scalarNode('serverName')->cannotBeEmpty()->isRequired();
    }

    protected function addProtocol(NodeBuilder $builder): void
    {
        // All protocols:
        // https://www.ibm.com/support/knowledgecenter/en/SSGU8G_11.50.0/com.ibm.admin.doc/ids_admin_0161.htm
        // Section "Connection-type field", but only some make sense in the extractor.
        $builder
            ->enumNode('protocol')
            ->values([OdbcDatabaseConfig::PROTOCOL_ONSOCTCP, OdbcDatabaseConfig::PROTOCOL_ONSOCSSL])
            ->defaultValue(OdbcDatabaseConfig::PROTOCOL_ONSOCTCP);
    }

    protected function addDbLocale(NodeBuilder $builder): void
    {
        $builder->scalarNode('dbLocale')->defaultValue('en_US.utf8');
    }

    protected function addHostNode(NodeBuilder $builder): void
    {
        $builder->scalarNode('host')->cannotBeEmpty()->isRequired();
    }

    protected function addDatabaseNode(NodeBuilder $builder): void
    {
        $builder->scalarNode('database')->cannotBeEmpty()->isRequired();
    }

    protected function addPortNode(NodeBuilder $builder): void
    {
        $builder->scalarNode('port')->cannotBeEmpty()->isRequired();
    }
}
