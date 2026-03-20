<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

use function is_string;
use Psr\Container\Container_Interface;
/**
 * Lazy listener instance for use with LazyListenerAggregate.
 *
 * Used as an internal class for the LazyAggregate to allow lazy creation of
 * listeners via a dependency injection container.
 *
 * Lazy event listener definitions add the following members to what the
 * LazyListener accepts:
 *
 * - event: the event name to attach to.
 * - priority: the priority at which to attach the listener, if not the default.
 *
 * @final This class should not be extended
 */
class Lazy_Event_Listener extends Lazy_Listener
{
    /** @var string Event name to which to attach. */
    private readonly string $event;
    /** @var null|int Priority at which to attach. */
    private readonly ?int $priority;
    public function __construct(array $definition, Container_Interface $container, array $env = [])
    {
        parent::__construct($definition, $container, $env);
        if (!isset($definition['event']) || !is_string($definition['event']) || empty($definition['event'])) {
            throw new Exception\InvalidArgumentException('Lazy listener definition is missing a valid "event" member; cannot create LazyListener');
        }
        $this->event = $definition['event'];
        $this->priority = isset($definition['priority']) ? (int) $definition['priority'] : null;
    }
    /**
     * @return string
     */
    public function get_event()
    {
        return $this->event;
    }
    /**
     * @param int $default
     * @return int
     */
    public function get_priority($default = 1)
    {
        return $this->priority ?? $default;
    }
}