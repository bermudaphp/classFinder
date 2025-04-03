<?php

namespace Bermuda\ClassFinder;

use Psr\Container\ContainerInterface;

class ConfigProvider
{
    public const CONFIG_KEY_MODE = '\Bermuda\ClassFinder:mode';
    public const CONFIG_KEY_FILTERS = '\Bermuda\ClassFinder:filters';
    public const CONFIG_KEY_LISTENERS = '\Bermuda\ClassFinder:listeners';

    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => $this->getFactories(),
            ]
        ];
    }
    
    protected function getFactories(): array
    {
        return [
            ClassFinderInterface::class => [ClassFinder::class, 'createFromContainer'],
            ClassFoundListenerProviderInterface::class => [ClassFoundListenerProvider::class, 'createFromContainer']
        ];
    }
}
