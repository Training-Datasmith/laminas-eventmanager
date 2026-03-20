<?php

declare (strict_types=1);
namespace Laminas\Event_Manager\Filter;

use Laminas\Event_Manager\Response_Collection;
/**
 * Interface for intercepting filter chains
 */
interface Filter_Interface
{
    /**
     * Execute the filter chain
     *
     * @param  string|object $context
     * @return mixed
     */
    public function run($context, array $params = []);
    /**
     * Attach an intercepting filter
     *
     * @return callable
     */
    public function attach(callable $callback);
    /**
     * Detach an intercepting filter
     *
     * @return bool
     */
    public function detach(callable $filter);
    /**
     * Get all intercepting filters
     *
     * @return array
     */
    public function get_filters();
    /**
     * Clear all filters
     *
     * @return void
     */
    public function clear_filters();
    /**
     * Get all filter responses
     *
     * @return ResponseCollection
     */
    public function get_responses();
}