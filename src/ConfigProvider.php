<?php

namespace Bermuda\ClassScanner;

use Psr\Container\ContainerInterface;
use function Bermuda\Config\conf;

class ConfigProvider extends \Bermuda\Config\ConfigProvider
{
    public const listeneresConfigKey = '\Bermuda\ClassScanner\listeners';
    
    protected function getFactories(): array
    {
        return [
            Scanner::class => static function(ContainerInterface $container): Scanner {
                
                $config = conf($container);
                if ($config->offsetExists(self::listeneresConfigKey)) {
                    return new Scanner(
                        $config->get(self::listeneresConfigKey),
                        $container->get(ClassFinderInterface::class)
                    );
                }
                
                return new Scanner(finder: $container->get(ClassFinderInterface::class));
            }
        ];
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
