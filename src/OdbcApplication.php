<?php

declare(strict_types=1);

namespace Keboola\DbExtractor;

use Psr\Log\LoggerInterface;
use Keboola\DbExtractor\Configuration\OdbcDbNode;
use Keboola\DbExtractorConfig\Configuration\ActionConfigRowDefinition;
use Keboola\DbExtractorConfig\Configuration\ConfigDefinition;
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
            } else {
                $this->config = new Config($config, new ActionConfigRowDefinition($dbNode));
            }
        } else {
            $this->config = new Config($config, new ConfigDefinition($dbNode));
        }
    }
}
