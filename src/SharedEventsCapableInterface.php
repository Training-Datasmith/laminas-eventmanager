<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

/**
 * Interface indicating that an object composes or can compose a
 * SharedEventManagerInterface instance.
 */
interface Shared_Events_Capable_Interface
{
    /**
     * Retrieve the shared event manager, if composed.
     *
     * @return null|SharedEventManagerInterface
     */
    public function get_shared_manager();
}