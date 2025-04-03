<?php

namespace Bermuda\ClassFinder;

interface FinalizedListenerInterface extends ClassFoundListenerInterface
{
    public function finalize(): void ;
}