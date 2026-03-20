<?php

declare (strict_types=1);
namespace Laminas\Event_Manager\Filter;

use function assert;
use function get_debug_type;
use function is_callable;
use Laminas\Event_Manager\Exception;
use Laminas\Stdlib\Fast_Priority_Queue;
use Return_Type_Will_Change;
use function sprintf;
/**
 * Specialized priority queue implementation for use with an intercepting
 * filter chain.
 *
 * Allows removal
 *
 * @template TValue of mixed
 * @template-extends FastPriorityQueue<TValue>
 * @final This class should not be extended
 */
class Filter_Iterator extends Fast_Priority_Queue
{
    /**
     * Does the queue contain a given value?
     *
     * @param  TValue $datum
     * @return bool
     */
    public function contains($datum)
    {
        foreach ($this as $item) {
            if ($item === $datum) {
                return true;
            }
        }
        return false;
    }
    /**
     * Insert a value into the queue.
     *
     * Requires a callable.
     *
     * @param callable $value
     * @param int $priority
     * @throws Exception\InvalidArgumentException For non-callable $value.
     */
    public function insert($value, $priority): void
    {
        if (!is_callable($value)) {
            throw new Exception\InvalidArgumentException(sprintf('%s can only aggregate callables; received %s', self::class, get_debug_type($value)));
        }
        parent::insert($value, $priority);
    }
    /**
     * Remove a value from the queue
     *
     * This is an expensive operation. It must first iterate through all values,
     * and then re-populate itself. Use only if absolutely necessary.
     *
     * @param  mixed $datum
     * @return bool
     */
    public function remove($datum)
    {
        $this->set_extract_flags(self::EXTR_BOTH);
        // Iterate and remove any matches
        $removed = false;
        $items = [];
        $this->rewind();
        while (!$this->is_empty()) {
            $item = $this->extract();
            if ($item['data'] === $datum) {
                $removed = true;
                continue;
            }
            $items[] = $item;
        }
        // Repopulate
        foreach ($items as $item) {
            $this->insert($item['data'], $item['priority']);
        }
        $this->set_extract_flags(self::EXTR_DATA);
        return $removed;
    }
    /**
     * Iterate the next filter in the chain
     *
     * Iterates and calls the next filter in the chain.
     *
     * @param  mixed $context
     * @param  FilterIterator $chain
     * @return mixed
     */
    #[Return_Type_Will_Change]
    public function next($context = null, array $params = [], $chain = null)
    {
        if (empty($context) || $chain instanceof Filter_Iterator && $chain->is_empty()) {
            return;
        }
        //We can't extract from an empty heap
        if ($this->is_empty()) {
            return;
        }
        $next = $this->extract();
        assert(is_callable($next));
        return $next($context, $params, $chain);
    }
}