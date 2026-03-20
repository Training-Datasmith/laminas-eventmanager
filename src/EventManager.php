<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

use function array_keys;
use function array_merge;
use function array_unique;
use ArrayObject;
use function get_debug_type;
use function is_callable;
use function is_string;
use function krsort;
use function sprintf;
/**
 * Event manager: notification system
 *
 * Use the EventManager when you want to create a per-instance notification
 * system for your objects.
 *
 * @final This class should not be extended
 */
class Event_Manager implements Event_Manager_Interface
{
    /**
     * Subscribed events and their listeners
     *
     * STRUCTURE:
     * [
     *     <string name> => [
     *         <int priority> => [
     *             0 => [<callable listener>, ...]
     *         ],
     *         ...
     *     ],
     *     ...
     * ]
     *
     * NOTE:
     * This structure helps us to reuse the list of listeners
     * instead of first iterating over it and generating a new one
     * -> In result it improves performance by up to 25% even if it looks a bit strange
     *
     * @var array<string, array<int, array{0: list<callable>}>>
     */
    protected $events = [];
    /** @var EventInterface Prototype to use when creating an event at trigger(). */
    protected $event_prototype;
    /**
     * Identifiers, used to pull shared signals from SharedEventManagerInterface instance
     *
     * @var array
     */
    protected $identifiers = [];
    /**
     * Shared event manager
     */
    protected ?\Laminas\Event_Manager\Shared_Event_Manager_Interface $shared_manager;
    /**
     * Constructor
     *
     * Allows optionally specifying identifier(s) to use to pull signals from a
     * SharedEventManagerInterface.
     */
    public function __construct(?Shared_Event_Manager_Interface $shared_event_manager = null, array $identifiers = [])
    {
        if ($shared_event_manager) {
            $this->shared_manager = $shared_event_manager;
            $this->set_identifiers($identifiers);
        }
        $this->event_prototype = new Event();
    }
    /**
     * @inheritDoc
     */
    public function set_event_prototype(Event_Interface $prototype): void
    {
        $this->event_prototype = $prototype;
    }
    /**
     * Retrieve the shared event manager, if composed.
     *
     * @return null|SharedEventManagerInterface $sharedEventManager
     */
    public function get_shared_manager()
    {
        return $this->shared_manager;
    }
    /**
     * @inheritDoc
     */
    public function get_identifiers()
    {
        return $this->identifiers;
    }
    /**
     * @inheritDoc
     */
    public function set_identifiers(array $identifiers): void
    {
        $this->identifiers = array_unique($identifiers);
    }
    /**
     * @inheritDoc
     */
    public function add_identifiers(array $identifiers): void
    {
        $this->identifiers = array_unique(array_merge($this->identifiers, $identifiers));
    }
    /**
     * @inheritDoc
     */
    public function trigger($event_name, $target = null, $argv = [])
    {
        $event = clone $this->event_prototype;
        $event->set_name($event_name);
        if ($target !== null) {
            $event->set_target($target);
        }
        if ($argv !== []) {
            $event->set_params($argv);
        }
        return $this->trigger_listeners($event);
    }
    /**
     * @inheritDoc
     */
    public function trigger_until(callable $callback, $event_name, $target = null, $argv = [])
    {
        $event = clone $this->event_prototype;
        $event->set_name($event_name);
        if ($target !== null) {
            $event->set_target($target);
        }
        if ($argv !== []) {
            $event->set_params($argv);
        }
        return $this->trigger_listeners($event, $callback);
    }
    /**
     * @inheritDoc
     */
    public function trigger_event(Event_Interface $event)
    {
        return $this->trigger_listeners($event);
    }
    /**
     * @inheritDoc
     */
    public function trigger_event_until(callable $callback, Event_Interface $event)
    {
        return $this->trigger_listeners($event, $callback);
    }
    /**
     * @inheritDoc
     */
    public function attach($event_name, callable $listener, $priority = 1): callable
    {
        if (!is_string($event_name)) {
            throw new Exception\InvalidArgumentException(sprintf('%s expects a string for the event; received %s', __METHOD__, get_debug_type($event_name)));
        }
        $this->events[$event_name][(int) $priority][0][] = $listener;
        return $listener;
    }
    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException For invalid event types.
     */
    public function detach(callable $listener, $event_name = null, $force = false): void
    {
        // If event is wildcard, we need to iterate through each listeners
        if (null === $event_name || '*' === $event_name && !$force) {
            foreach (array_keys($this->events) as $event_name) {
                $this->detach($listener, $event_name, true);
            }
            return;
        }
        if (!is_string($event_name)) {
            throw new Exception\InvalidArgumentException(sprintf('%s expects a string for the event; received %s', __METHOD__, get_debug_type($event_name)));
        }
        if (!isset($this->events[$event_name])) {
            return;
        }
        foreach ($this->events[$event_name] as $priority => $listeners) {
            foreach ($listeners[0] as $index => $evaluated_listener) {
                if ($evaluated_listener !== $listener) {
                    continue;
                }
                // Found the listener; remove it.
                unset($this->events[$event_name][$priority][0][$index]);
                // If the queue for the given priority is empty, remove it.
                if (empty($this->events[$event_name][$priority][0])) {
                    unset($this->events[$event_name][$priority]);
                    break;
                }
            }
        }
        // If the queue for the given event is empty, remove it.
        if (empty($this->events[$event_name])) {
            unset($this->events[$event_name]);
        }
    }
    /**
     * @inheritDoc
     */
    public function clear_listeners($event_name): void
    {
        if (isset($this->events[$event_name])) {
            unset($this->events[$event_name]);
        }
    }
    /**
     * Prepare arguments
     *
     * Use this method if you want to be able to modify arguments from within a
     * listener. It returns an ArrayObject of the arguments, which may then be
     * passed to trigger().
     *
     * @template Tk of array-key
     * @template Tv
     * @param  array<Tk, Tv> $args
     * @return ArrayObject<Tk, Tv>
     */
    public function prepare_args(array $args): \ArrayObject
    {
        return new ArrayObject($args);
    }
    /**
     * Trigger listeners
     *
     * Actual functionality for triggering listeners, to which trigger() delegate.
     */
    protected function trigger_listeners(Event_Interface $event, ?callable $callback = null): \Laminas\Event_Manager\Response_Collection
    {
        $name = $event->get_name();
        if ($name === null || $name === '' || $name === '0') {
            throw new Exception\RuntimeException('Event is missing a name; cannot trigger!');
        }
        if (isset($this->events[$name])) {
            $list_of_listeners_by_priority = $this->events[$name];
            if (isset($this->events['*'])) {
                foreach ($this->events['*'] as $priority => $list_of_listeners) {
                    $list_of_listeners_by_priority[$priority][] = $list_of_listeners[0];
                }
            }
        } elseif (isset($this->events['*'])) {
            $list_of_listeners_by_priority = $this->events['*'];
        } else {
            $list_of_listeners_by_priority = [];
        }
        if ($this->shared_manager) {
            foreach ($this->shared_manager->get_listeners($this->identifiers, $name) as $priority => $listeners) {
                $list_of_listeners_by_priority[$priority][] = $listeners;
            }
        }
        // Sort by priority in reverse order
        krsort($list_of_listeners_by_priority);
        // Initial value of stop propagation flag should be false
        $event->stop_propagation(false);
        // Execute listeners
        $responses = new Response_Collection();
        foreach ($list_of_listeners_by_priority as $list_of_listeners) {
            foreach ($list_of_listeners as $listeners) {
                foreach ($listeners as $listener) {
                    $response = $listener($event);
                    $responses->push($response);
                    // If the event was asked to stop propagating, do so
                    if ($event->propagation_is_stopped()) {
                        $responses->set_stopped(true);
                        return $responses;
                    }
                    // If the result causes our validation callback to return true,
                    // stop propagation
                    if (is_callable($callback) && $callback($response)) {
                        $responses->set_stopped(true);
                        return $responses;
                    }
                }
            }
        }
        return $responses;
    }
}