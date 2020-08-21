<?php

declare(strict_types=1);

namespace Keboola\DbExtractor;

use Keboola\DbExtractor\Exception\UserException;
use Keboola\DbExtractorConfig\Configuration\GetTablesListFilterDefinition;
use Psr\Log\LoggerInterface;
use Keboola\DbExtractor\Configuration\OdbcDbNode;
use Keboola\DbExtractorConfig\Configuration\ActionConfigRowDefinition;
use Keboola\DbExtractorConfig\Configuration\ConfigRowDefinition;
use Keboola\DbExtractorConfig\Config;

class OdbcApplication extends Application
{
    public function __construct(array $config, LoggerInterface $logger, array $state = [], string $dataDir = '/data/')
    {
        $config['parameters']['data_dir'] = $dataDir;
        $config['parameters']['extractor_class'] = 'OdbcExtractor';
        parent::__construct($config, $logger, $state);
    }

    protected function buildConfig(array $config): void
    {
        $dbNode = new OdbcDbNode();
        if ($this->isRowConfiguration($config)) {
            if ($this['action'] === 'run') {
                $this->config = new Config($config, new ConfigRowDefinition($dbNode));
            } elseif ($this['action'] === 'getTables') {
                // Tables and columns can be loaded separately
                $this->config = new Config($config, new GetTablesListFilterDefinition($dbNode));
            } else {
                $this->config = new Config($config, new ActionConfigRowDefinition($dbNode));
            }
        } else {
            throw new UserException('The old configuration format is not supported. Please use config rows.');
        }
    }
}
