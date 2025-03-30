<?php

namespace Bermuda\ClassScanner;

use Psr\Container\ContainerInterface;

class ConfigProvider
{
    public const CONFIG_KEY_MODE = '\Bermuda\ClassScanner:flags';
    public const CONFIG_KEY_FILTERS = '\Bermuda\ClassScanner:filters';
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
        return [
            Scanner::class => [ScannerFactory::class, 'createFromContainer'],
            ClassFinderInterface::class => [ClassFinder::class, 'createFromContainer'],
        ];
    }
}
