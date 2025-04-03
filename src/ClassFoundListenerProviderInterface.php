<?php

namespace Bermuda\ClassFinder;

interface ClassFoundListenerProviderInterface
{
    /**
     * @return iterable<ClassFoundListenerInterface>
     */
    public function getClassFoundListeners(): iterable ;

    public function addListener(ClassFoundListenerInterface $listener): void ;
}