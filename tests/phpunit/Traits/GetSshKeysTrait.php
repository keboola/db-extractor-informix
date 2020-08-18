<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Tests\Traits;

trait GetSshKeysTrait
{
    protected function getPrivateKey(): string
    {
        return (string) file_get_contents('/root/.ssh/id_rsa');
    }

    protected function getPublicKey(): string
    {
        return (string) file_get_contents('/root/.ssh/id_rsa.pub');
    }
}
