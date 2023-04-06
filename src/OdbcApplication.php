<?php

declare(strict_types=1);

namespace Keboola\DbExtractor;

use Keboola\DbExtractor\Configuration\OdbcDbNode;
use Keboola\DbExtractor\Exception\UserException;
use Keboola\DbExtractorConfig\Config;
use Keboola\DbExtractorConfig\Configuration\ActionConfigRowDefinition;
use Keboola\DbExtractorConfig\Configuration\ConfigRowDefinition;
use Keboola\DbExtractorConfig\Configuration\GetTablesListFilterDefinition;
use Psr\Log\LoggerInterface;

class OdbcApplication extends Application
{
    protected function loadConfig(): void
    {
        $config = $this->getRawConfig();
        $action = $config['action'] ?? 'run';

        $config['parameters']['extractor_class'] = 'OdbcExtractor';
        $config['parameters']['data_dir'] = $this->getDataDir();

        $dbNode = new OdbcDbNode();
        if ($this->isRowConfiguration($config)) {
            if ($action === 'run') {
                $this->config = new Config($config, new ConfigRowDefinition($dbNode));
            } elseif ($config['action'] === 'getTables') {
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
