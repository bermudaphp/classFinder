<?php

namespace Bermuda\ClassScanner;

use Psr\Container\ContainerInterface;
use function Bermuda\Config\conf;

final class ScannerFactory
{
    public static function createFromContainer(ContainerInterface $container): Scanner
    {
        return new Scanner(
            $container->get('config')->get(ConfigProvider::CONFIG_KEY_LISTENERS, []),
            $container->get(ClassFinderInterface::class)
        );
    }
}
