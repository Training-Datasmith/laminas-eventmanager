<?php

declare (strict_types=1);
namespace Laminas\Event_Manager;

use ArrayAccess;
/**
 * Representation of an event
 *
 * @template-covariant TTarget of object|string|null
 * @template-covariant TParams of array|ArrayAccess|object
 */
interface Event_Interface
{
    /**
     * Get event name
     *
     * @return string|null
     */
    public function get_name();
    /**
     * Get target/context from which event was triggered
     *
     * @return object|string|null
     * @psalm-return TTarget
     */
    public function get_target();
    /**
     * Get parameters passed to the event
     *
     * @return array|ArrayAccess|object
     * @psalm-return TParams
     */
    public function get_params();
    /**
     * Get a single parameter by name
     *
     * @param  string|int $name
     * @param  mixed $default Default value to return if parameter does not exist
     * @return mixed
     */
    public function get_param($name, $default = null);
    /**
     * Set the event name
     *
     * @param  string $name
     * @return void
     */
    public function set_name($name);
    /**
     * Set the event target/context
     *
     * @param object|string|null $target
     * @template NewTTarget of object|string|null
     * @psalm-param NewTTarget $target
     * @psalm-this-out static&self<NewTTarget, TParams>
     * @return void
     */
    public function set_target($target);
    /**
     * Set event parameters. Overwrites parameters.
     *
     * @param array|ArrayAccess|object $params
     * @template NewTParams of array|ArrayAccess|object
     * @psalm-param NewTParams $params
     * @psalm-this-out static&self<TTarget, NewTParams>
     * @return void
     */
    public function set_params($params);
    /**
     * Set a single parameter by key
     *
     * @param  string|int $name
     * @param  mixed $value
     * @return void
     */
    public function set_param($name, $value);
    /**
     * Indicate whether or not the parent EventManagerInterface should stop propagating events
     *
     * @param  bool $flag
     * @return void
     */
    public function stop_propagation($flag = true);
    /**
     * Has this event indicated event propagation should stop?
     *
     * @return bool
     */
    public function propagation_is_stopped();
}