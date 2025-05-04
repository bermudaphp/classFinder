<?php

namespace Bermuda\ClassFinder;

/**
 * Interface FinalizedListenerInterface
 *
 * Extends ClassFoundListenerInterface by adding a finalize() method.
 * Implementers of this interface will receive notifications for each found class or function
 * via the handle() method (inherited from ClassFoundListenerInterface) during the scanning process.
 * Once the scanning is complete, the finalize() method is called to perform any cleanup or final processing.
 */
interface FinalizedListenerInterface extends ClassFoundListenerInterface
{
    /**
     * Called at the end of the discovery process to perform final actions such as cleanup or final reporting.
     *
     * @return void
     */
    public function finalize(): void;
}
