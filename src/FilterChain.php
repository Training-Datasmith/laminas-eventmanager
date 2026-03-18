<?php

declare(strict_types=1);

namespace Laminas\EventManager;

/**
 * FilterChain: intercepting filter manager
 *
 * @final This class should not be extended
 */
class FilterChain implements Filter\FilterInterface
{
    /** @var Filter\FilterIterator All filters */
    protected \Laminas\EventManager\Filter\FilterIterator $filters;

    /**
     * Constructor
     *
     * Initializes Filter\FilterIterator in which filters will be aggregated
     */
    public function __construct()
    {
        $this->filters = new Filter\FilterIterator();
    }

    /**
     * Apply the filters
     *
     * Begins iteration of the filters.
     *
     * @param  mixed $context Object under observation
     * @param  mixed $argv Associative array of arguments
     * @return mixed
     */
    public function run($context, array $argv = [])
    {
        $chain = clone $this->getFilters();

        if ($chain->isEmpty()) {
            return;
        }

        $next = $chain->extract();

        return $next($context, $argv, $chain);
    }

    /**
     * Connect a filter to the chain
     *
     * @param  callable $callback PHP Callback
     * @param  int $priority Priority in the queue at which to execute;
     *     defaults to 1 (higher numbers == higher priority)
     * @return CallbackHandler (to allow later unsubscribe)
     * @throws Exception\InvalidCallbackException
     */
    public function attach(callable $callback, $priority = 1): callable
    {
        $this->filters->insert($callback, $priority);
        return $callback;
    }

    /**
     * Detach a filter from the chain
     *
     * @return bool Returns true if filter found and unsubscribed; returns false otherwise
     */
    public function detach(callable $filter)
    {
        return $this->filters->remove($filter);
    }

    /**
     * Retrieve all filters
     *
     * @return Filter\FilterIterator
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): void
    {
        $this->filters = new Filter\FilterIterator();
    }

    /**
     * Return current responses
     *
     * Only available while the chain is still being iterated. Returns the
     * current ResponseCollection.
     *
     * @return null|ResponseCollection
     */
    public function getResponses(): null
    {
        return null;
    }
}
