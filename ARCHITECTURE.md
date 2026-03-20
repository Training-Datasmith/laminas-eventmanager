# Architecture: laminas-eventmanager

## Purpose
A PHP event system library. Provides publish/subscribe event dispatching with priority-ordered listeners, shared event management across component boundaries, filter chains, and lazy listener loading.

## Directory Structure
```
src/
  Event.php                         # Default event value object (name, target, params)
  Event_Interface.php               # Contract for all events
  Event_Manager.php                 # Core event dispatcher — attach/detach/trigger
  Event_Manager_Interface.php       # Contract for event managers
  Event_Manager_Aware_Interface.php # Marks classes that own an event manager
  Event_Manager_Aware_Trait.php     # Default implementation of the aware interface
  Events_Capable_Interface.php      # Marks classes that expose getEventManager()
  Listener_Aggregate_Interface.php  # Bundles multiple listeners into one attachable unit
  Listener_Aggregate_Trait.php      # Default implementation of detachAggregate()
  Abstract_Listener_Aggregate.php   # Convenience base class for aggregates
  Shared_Event_Manager.php          # Cross-component event sharing (wildcard identifiers)
  Shared_Event_Manager_Interface.php
  Shared_Events_Capable_Interface.php
  Filter_Chain.php                  # Middleware-style filter chain (pre/post processing)
  Filter/
    Filter_Interface.php
    Filter_Iterator.php             # Priority-sorted filter iterator
  Lazy_Listener.php                 # Defers service resolution until event fires
  Lazy_Event_Listener.php           # Lazy listener for specific events
  Lazy_Listener_Aggregate.php       # Lazy aggregate of listeners
  Response_Collection.php           # Collects per-listener return values; supports short-circuit
  Exception/                        # Typed exceptions
  Test/Event_Listener_Introspection_Trait.php  # PHPUnit helpers for inspecting attached listeners
```

## Key Design Decisions
- **Priority queue** — listeners are stored in a priority-ordered `SplPriorityQueue`. Higher priority listeners run first; listeners at the same priority run in FIFO order.
- **Short-circuit propagation** — `Response_Collection::setStopped(true)` (or returning `false` from a listener) halts further listener dispatch.
- **Shared event manager** — `Shared_Event_Manager` allows listeners attached to a class identifier to receive events from all instances of that class, enabling cross-component integration without tight coupling.
- **Lazy listener loading** — `Lazy_Listener` accepts a PSR-11 container + service name; the actual service is resolved only when the event fires, avoiding eager loading of expensive dependencies.
- **Filter chains** — `Filter_Chain` implements a middleware pipeline where each filter can modify the event or its response before passing to the next.

## Extension Points
- Implement `Listener_Aggregate_Interface` to group related listeners and attach/detach them atomically.
- Implement `Event_Interface` to add domain-specific event properties.
- Use `Shared_Event_Manager` to listen to events from third-party components by their class name.

## Dependency Flow
```
EventManager::trigger('event.name', $target, $params)
  └─ priority-sorted listener queue
       └─ each callable listener($event) → Response_Collection
            └─ short-circuit if stopped or listener returns false

SharedEventManager
  └─ attached to EventManager via addIdentifiers()
       └─ listeners attached to a class name receive all events from that component
```
