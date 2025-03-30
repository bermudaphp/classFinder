<?php

namespace Bermuda\ClassScanner;

use Psr\Container\ContainerInterface;

class ConfigProvider
{
    public const CONFIG_KEY_LISTENERS = '\Bermuda\ClassScanner:listeners';
    
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
