<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

use function get_debug_type;
use function is_array;
use Psr\Container\Container_Interface;
use function sprintf;
/**
 * Aggregate listener for attaching lazy listeners.
 *
 * Lazy listeners are listeners where creation is deferred until they are
 * triggered; this removes the most costly mechanism of pulling a listener
 * from a container unless the listener is actually invoked.
 *
 * Usage is:
 *
 * <code>
 * $aggregate = new LazyListenerAggregate(
 *     $lazyEventListenersOrDefinitions,
 *     $container
 * );
 * $aggregate->attach($events);
 * </code>
 *
 * @final This class should not be extended
 */
class Lazy_Listener_Aggregate implements Listener_Aggregate_Interface
{
    use Listener_Aggregate_Trait;
    /**
     * Generated LazyEventListener instances.
     *
     * @var LazyEventListener[]
     */
    private array $lazy_listeners = [];
    /**
     * Constructor
     *
     * Accepts the composed $listeners, as well as the $container and $env in
     * order to create a listener aggregate that defers listener creation until
     * the listener is triggered.
     *
     * Listeners may be either LazyEventListener instances, or lazy event
     * listener definitions that can be provided to a LazyEventListener
     * constructor in order to create a new instance; in the latter case, the
     * $container and $env will be passed at instantiation as well.
     *
     * @param array $listeners LazyEventListener instances or array definitions
     *     to pass to the LazyEventListener constructor.
     * @throws Exception\InvalidArgumentException For invalid listener items.
     */
    public function __construct(
        array $listeners,
        /**
         * @var ContainerInterface Container from which to pull lazy listeners
         */
        private Container_Interface $container,
        /**
         * @var array Additional environment/option variables to use when creating listener
         */
        private array $env = []
    )
    {
        // This would raise an exception for invalid structs
        foreach ($listeners as $listener) {
            if (is_array($listener)) {
                $listener = new Lazy_Event_Listener($listener, $container, $env);
            }
            if (!$listener instanceof Lazy_Event_Listener) {
                throw new Exception\InvalidArgumentException(sprintf('All listeners must be LazyEventListener instances or definitions; received %s', get_debug_type($listener)));
            }
            $this->lazy_listeners[] = $listener;
        }
    }
    /**
     * Attach the aggregate to the event manager.
     *
     * Loops through all composed lazy listeners, and attaches them to the
     * event manager.
     *
     * @param int $priority
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        foreach ($this->lazy_listeners as $lazy_listener) {
            $this->listeners[] = $events->attach($lazy_listener->get_event(), $lazy_listener, $lazy_listener->get_priority($priority));
        }
    }
}