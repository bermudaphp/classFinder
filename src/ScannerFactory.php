<?php

namespace Bermuda\ClassScanner;

use Psr\Container\ContainerInterface;
use function Bermuda\Config\conf;

final class ScannerFactory
{
    public const fromContainer = '\Bermuda\ClassScanner\ScannerFactory::fromContainer';
    public static function fromContainer(ContainerInterface $container): Scanner
    {
        $config = conf($container);
        if ($config->offsetGet('app')->offsetExists(self::listeneresConfigKey)) {
            $config = $config->offsetGet('app');
            return new Scanner(
                $config->get(static::listeneresConfigKey),
                $container->get(ClassFinderInterface::class)
            );
        }

        return new Scanner(finder: $container->get(ClassFinderInterface::class));
    }
}
