<?php

declare(strict_types=1);

namespace Laminas\EventManager;

/**
 * Interface indicating that an object composes or can compose a
 * SharedEventManagerInterface instance.
 */
interface SharedEventsCapableInterface
{
    /**
     * Retrieve the shared event manager, if composed.
     *
     * @return null|SharedEventManagerInterface
     */
    public function getSharedManager();
}
