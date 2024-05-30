<?php

namespace Bermuda\ClassScanner;

use Psr\Container\ContainerInterface;
use function Bermuda\Config\conf;

class ConfigProvider extends \Bermuda\Config\ConfigProvider
{
    public const listeneresConfigKey = '\Bermuda\ClassScanner\listeners';
    
    protected function getFactories(): array
    {
        return [Scanner::class => ScannerFactory::fromContainer];
    }

    protected function getAliases(): array
    {
        return [ClassFinderInterface::class => ClassFinder::class];
    }

    protected function getInvokables(): array
    {
        return [ClassFinder::class];
    }
}
