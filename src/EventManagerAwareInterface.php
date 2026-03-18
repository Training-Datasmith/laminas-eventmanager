<?php

declare(strict_types=1);

namespace Laminas\EventManager;

/**
 * Interface to automate setter injection for an EventManager instance
 */
interface EventManagerAwareInterface extends EventsCapableInterface
{
    /**
     * Inject an EventManager instance
     *
     * @return void
     */
    public function setEventManager(EventManagerInterface $eventManager);
}
