<?php

namespace Bermuda\ClassScanner;

use Psr\Container\ContainerInterface;

final class ScannerFactory
{
    public static function createFromContainer(ContainerInterface $container): Scanner
    {
        $listeners = [];

        foreach ($container->get('config')->get(ConfigProvider::CONFIG_KEY_LISTENERS, []) as $id) {
            $listeners[] = $container->get($id);
        }

        return new Scanner(
            $listeners,
            $container->get(ClassFinderInterface::class)
        );
    }
}
