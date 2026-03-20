<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

/**
 * Interface indicating that an object composes an EventManagerInterface instance.
 */
interface Events_Capable_Interface
{
    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function get_event_manager();
}