<?php

declare (strict_types=1);
namespace Laminas\Event_Manager\Test;

use function array_keys;
use function array_merge;
use function iterator_to_array;
use function krsort;
use Laminas\Event_Manager\Event_Manager;
use Php_Unit\Framework\Assert;
use ReflectionProperty;
use const SORT_NUMERIC;
use function sprintf;
use Traversable;
/**
 * Trait providing utility methods and assertions for use in PHPUnit test cases.
 *
 * This trait may be composed into a test case, and provides:
 *
 * - methods for introspecting events and listeners
 * - methods for asserting listeners are attached at a specific priority
 *
 * Some functionality in this trait duplicates functionality present in the
 * version 2 EventManagerInterface and/or EventManager implementation, but
 * abstracts that functionality for use in v3. As such, components or code
 * that is testing for listener registration should use the methods in this
 * trait to ensure tests are forwards-compatible between laminas-eventmanager
 * versions.
 */
trait Event_Listener_Introspection_Trait
{
    /**
     * Retrieve a list of event names from an event manager.
     *
     * @return string[]
     */
    private function get_events_from_event_manager(Event_Manager $events)
    {
        $r = new ReflectionProperty($events, 'events');
        $listeners = $r->get_value($events);
        return array_keys($listeners);
    }
    /**
     * Retrieve an interable list of listeners for an event.
     *
     * Given an event and an event manager, returns an iterator with the
     * listeners for that event, in priority order.
     *
     * If $withPriority is true, the key values will be the priority at which
     * the given listener is attached.
     *
     * Do not pass $withPriority if you want to cast the iterator to an array,
     * as many listeners will likely have the same priority, and thus casting
     * will collapse to the last added.
     *
     * @param string $event
     * @param bool $withPriority
     * @return Traversable
     */
    private function get_listeners_for_event($event, Event_Manager $events, $with_priority = false)
    {
        $r = new ReflectionProperty($events, 'events');
        $internal = $r->get_value($events);
        $listeners = [];
        foreach ($internal[$event] ?? [] as $p => $list_of_listeners) {
            foreach ($list_of_listeners as $l) {
                $listeners[$p] = isset($listeners[$p]) ? array_merge($listeners[$p], $l) : $l;
            }
        }
        return $this->traverse_listeners($listeners, $with_priority);
    }
    /**
     * Assert that a given listener exists at the specified priority.
     *
     * @param int $expectedPriority
     * @param string $event
     * @param string $message Failure message to use, if any.
     */
    private function assert_listener_at_priority(callable $expected_listener, $expected_priority, $event, Event_Manager $events, $message = '')
    {
        $message = $message ?: sprintf('Listener not found for event "%s" and priority %d', $event, $expected_priority);
        $listeners = $this->get_listeners_for_event($event, $events, true);
        $found = false;
        foreach ($listeners as $priority => $listener) {
            if ($listener === $expected_listener && $priority === $expected_priority) {
                $found = true;
                break;
            }
        }
        Assert::assert_true($found, $message);
    }
    /**
     * Returns an indexed array of listeners for an event.
     *
     * Returns an indexed array of listeners for an event, in priority order.
     * Priority values will not be included; use this only for testing if
     * specific listeners are present, or for a count of listeners.
     *
     * @param string $event
     * @return callable[]
     */
    private function get_array_of_listeners_for_event($event, Event_Manager $events)
    {
        return iterator_to_array($this->get_listeners_for_event($event, $events));
    }
    /**
     * Generator for traversing listeners in priority order.
     *
     * @param bool $withPriority When true, yields priority as key.
     * @return iterable
     */
    public function traverse_listeners(array $queue, $with_priority = false)
    {
        krsort($queue, SORT_NUMERIC);
        foreach ($queue as $priority => $listeners) {
            $priority = (int) $priority;
            foreach ($listeners as $listener) {
                if ($with_priority) {
                    yield $priority => $listener;
                } else {
                    yield $listener;
                }
            }
        }
    }
}