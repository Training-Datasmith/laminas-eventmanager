<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

/**
 * Interface to automate setter injection for an EventManager instance
 */
interface Event_Manager_Aware_Interface extends Events_Capable_Interface
{
    /**
     * Inject an EventManager instance
     *
     * @return void
     */
    public function set_event_manager(Event_Manager_Interface $event_manager);
}