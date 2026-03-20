<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

/**
 * Provides logic to easily create aggregate listeners, without worrying about
 * manually detaching events
 */
trait Listener_Aggregate_Trait
{
    /** @var callable[] */
    protected $listeners = [];
    /**
     * {@inheritDoc}
     */
    public function detach(Event_Manager_Interface $events): void
    {
        foreach ($this->listeners as $index => $callback) {
            $events->detach($callback);
            unset($this->listeners[$index]);
        }
    }
}