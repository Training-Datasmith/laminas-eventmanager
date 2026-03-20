<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

use function array_keys;
use function array_merge;
use function get_debug_type;
use function is_string;
use function sprintf;
/**
 * Shared/contextual EventManager
 *
 * Allows attaching to EMs composed by other classes without having an instance first.
 * The assumption is that the SharedEventManager will be injected into EventManager
 * instances, and then queried for additional listeners when triggering an event.
 *
 * @final This class should not be extended
 */
class Shared_Event_Manager implements Shared_Event_Manager_Interface
{
    /**
     * Identifiers with event connections
     *
     * @var array
     */
    protected $identifiers = [];
    /**
     * Attach a listener to an event emitted by components with specific identifiers.
     *
     * As an example, the following connects to the "getAll" event of both an
     * AbstractResource and EntityResource:
     *
     * <code>
     * $sharedEventManager = new SharedEventManager();
     * foreach (['My\Resource\AbstractResource', 'My\Resource\EntityResource'] as $identifier) {
     *     $sharedEventManager->attach(
     *         $identifier,
     *         'getAll',
     *         function ($e) use ($cache) {
     *             if (!$id = $e->getParam('id', false)) {
     *                 return;
     *             }
     *             if (!$data = $cache->load(get_class($resource) . '::getOne::' . $id )) {
     *                 return;
     *             }
     *             return $data;
     *         }
     *     );
     * }
     * </code>
     *
     * @param  string $identifier Identifier for event emitting component.
     * @param  string $event
     * @param  callable $listener Listener that will handle the event.
     * @param  int $priority Priority at which listener should execute
     * @throws Exception\InvalidArgumentException For invalid identifier arguments.
     * @throws Exception\InvalidArgumentException For invalid event arguments.
     */
    public function attach($identifier, $event, callable $listener, $priority = 1): void
    {
        if (!is_string($identifier) || empty($identifier)) {
            throw new Exception\InvalidArgumentException(sprintf('Invalid identifier provided; must be a string; received "%s"', get_debug_type($identifier)));
        }
        if (!is_string($event) || empty($event)) {
            throw new Exception\InvalidArgumentException(sprintf('Invalid event provided; must be a non-empty string; received "%s"', get_debug_type($event)));
        }
        $this->identifiers[$identifier][$event][(int) $priority][] = $listener;
    }
    /**
     * @inheritDoc
     */
    public function detach(callable $listener, $identifier = null, $event_name = null, $force = false): void
    {
        // No identifier or wildcard identifier: loop through all identifiers and detach
        if (null === $identifier || '*' === $identifier && !$force) {
            foreach (array_keys($this->identifiers) as $identifier) {
                $this->detach($listener, $identifier, $event_name, true);
            }
            return;
        }
        if (!is_string($identifier) || empty($identifier)) {
            throw new Exception\InvalidArgumentException(sprintf('Invalid identifier provided; must be a string, received %s', get_debug_type($identifier)));
        }
        // Do we have any listeners on the provided identifier?
        if (!isset($this->identifiers[$identifier])) {
            return;
        }
        if (null === $event_name || '*' === $event_name && !$force) {
            foreach (array_keys($this->identifiers[$identifier]) as $event_name) {
                $this->detach($listener, $identifier, $event_name, true);
            }
            return;
        }
        if (!is_string($event_name) || empty($event_name)) {
            throw new Exception\InvalidArgumentException(sprintf('Invalid event name provided; must be a string, received %s', get_debug_type($event_name)));
        }
        if (!isset($this->identifiers[$identifier][$event_name])) {
            return;
        }
        foreach ($this->identifiers[$identifier][$event_name] as $priority => $listeners) {
            foreach ($listeners as $index => $evaluated_listener) {
                if ($evaluated_listener !== $listener) {
                    continue;
                }
                // Found the listener; remove it.
                unset($this->identifiers[$identifier][$event_name][$priority][$index]);
                // Is the priority queue empty?
                if (empty($this->identifiers[$identifier][$event_name][$priority])) {
                    unset($this->identifiers[$identifier][$event_name][$priority]);
                    break;
                }
            }
            // Is the event queue empty?
            if (empty($this->identifiers[$identifier][$event_name])) {
                unset($this->identifiers[$identifier][$event_name]);
                break;
            }
        }
        // Is the identifier queue now empty? Remove it.
        if (empty($this->identifiers[$identifier])) {
            unset($this->identifiers[$identifier]);
        }
    }
    /**
     * Retrieve all listeners for a given identifier and event
     *
     * @param  string[] $identifiers
     * @param  string   $eventName
     * @return array[]
     * @throws Exception\InvalidArgumentException
     */
    public function get_listeners(array $identifiers, $event_name): array
    {
        if ('*' === $event_name || !is_string($event_name) || empty($event_name)) {
            throw new Exception\InvalidArgumentException(sprintf('Event name passed to %s must be a non-empty, non-wildcard string', __METHOD__));
        }
        $return_listeners = [];
        foreach ($identifiers as $identifier) {
            if ('*' === $identifier || !is_string($identifier) || empty($identifier)) {
                throw new Exception\InvalidArgumentException(sprintf('Identifier names passed to %s must be non-empty, non-wildcard strings', __METHOD__));
            }
            if (isset($this->identifiers[$identifier])) {
                $listeners_by_identifier = $this->identifiers[$identifier];
                if (isset($listeners_by_identifier[$event_name])) {
                    foreach ($listeners_by_identifier[$event_name] as $priority => $listeners) {
                        $return_listeners[$priority][] = $listeners;
                    }
                }
                if (isset($listeners_by_identifier['*'])) {
                    foreach ($listeners_by_identifier['*'] as $priority => $listeners) {
                        $return_listeners[$priority][] = $listeners;
                    }
                }
            }
        }
        if (isset($this->identifiers['*'])) {
            $wildcard_identifier = $this->identifiers['*'];
            if (isset($wildcard_identifier[$event_name])) {
                foreach ($wildcard_identifier[$event_name] as $priority => $listeners) {
                    $return_listeners[$priority][] = $listeners;
                }
            }
            if (isset($wildcard_identifier['*'])) {
                foreach ($wildcard_identifier['*'] as $priority => $listeners) {
                    $return_listeners[$priority][] = $listeners;
                }
            }
        }
        foreach ($return_listeners as $priority => $list_of_listeners) {
            $return_listeners[$priority] = array_merge(...$list_of_listeners);
        }
        return $return_listeners;
    }
    /**
     * @inheritDoc
     */
    public function clear_listeners($identifier, $event_name = null)
    {
        if (!isset($this->identifiers[$identifier])) {
            return false;
        }
        if (null === $event_name) {
            unset($this->identifiers[$identifier]);
            return;
        }
        if (!isset($this->identifiers[$identifier][$event_name])) {
            return;
        }
        unset($this->identifiers[$identifier][$event_name]);
    }
}