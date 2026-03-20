<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

use function array_merge;
use function array_unique;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;
use Traversable;
/**
 * A trait for objects that provide events.
 *
 * If you use this trait in an object, you will probably want to also implement
 * EventManagerAwareInterface, which will make it so the default initializer in
 * a Laminas MVC application will automatically inject an instance of the
 * EventManager into your object when it is pulled from the ServiceManager.
 *
 * @see Laminas\Mvc\Service\ServiceManagerConfig
 */
trait Event_Manager_Aware_Trait
{
    /** @var EventManagerInterface */
    protected $events;
    /**
     * Set the event manager instance used by this context.
     *
     * For convenience, this method will also set the class name / LSB name as
     * identifiers, in addition to any string or array of strings set to the
     * $this->eventIdentifier property.
     */
    public function set_event_manager(Event_Manager_Interface $events): void
    {
        $identifiers = [self::class, static::class];
        if (isset($this->event_identifier)) {
            if (is_string($this->event_identifier) || is_array($this->event_identifier) || $this->event_identifier instanceof Traversable) {
                $identifiers = array_unique(array_merge($identifiers, (array) $this->event_identifier));
            } elseif (is_object($this->event_identifier)) {
                $identifiers[] = $this->event_identifier;
            }
            // silently ignore invalid eventIdentifier types
        }
        $events->set_identifiers($identifiers);
        $this->events = $events;
        if (method_exists($this, 'attachDefaultListeners')) {
            $this->attach_default_listeners();
        }
    }
    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function get_event_manager()
    {
        if (!$this->events instanceof Event_Manager_Interface) {
            $this->set_event_manager(new Event_Manager());
        }
        return $this->events;
    }
}