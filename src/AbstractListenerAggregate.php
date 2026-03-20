<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

/**
 * Abstract aggregate listener
 */
abstract class Abstract_Listener_Aggregate implements Listener_Aggregate_Interface
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