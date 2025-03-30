<?php

namespace Bermuda\ClassScanner;

use Psr\Container\ContainerInterface;

class ConfigProvider
{
    public const CONFIG_KEY_LISTENERS = '\Bermuda\ClassScanner:listeners';

    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => $this->getFactories(),
                'aliases' => $this->getAliases(),
                'invokables' => $this->getInvokables()
            ]
        ];
    }
    
    protected function getFactories(): array
    {
        return [Scanner::class => ScannerFactory::createFromContainer];
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
