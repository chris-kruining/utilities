<?php

namespace CPB\Utilities\Collections
{
    use Core\Utility\Exception\Deprecated;
    use CPB\Utilities\Collections\Exception\KeyMissing;
    use CPB\Utilities\Common\CollectionInterface;
    use CPB\Utilities\Common\Exceptions\NotFound;
    use CPB\Utilities\Contracts\Resolvable;
    use CPB\Utilities\Math\Arithmetic;

    class Collection implements CollectionInterface
    {
        protected
            $items
        ;

        public const
            UNDEFINED = '__UNDEFINED__'
        ;

        public function __construct()
        {
            $this->items = [];
        }

        public function __clone()
        {
            $iterator = function($arr) use(&$iterator): array
            {
                foreach($arr as &$item)
                {
                    if(\is_object($item))
                    {
                        $item = clone $item;
                    }
                    elseif(\is_array($item))
                    {
                        $item = $iterator($item);
                    }
                }

                unset($item);

                return $arr;
            };

            $this->items = $iterator($this->items);
        }

        public function __toString(): string
        {
            return $this->toString();
        }

        /**
         * Serializes Collection
         */
        public function __serialize(): array
        {
            return $this->items;
        }

        /**
         * Unserialize data
         */
        public function __unserialize(array $data): void
        {
            $this->items = $data;
        }

        public function __debugInfo(): array
        {
            return $this->items ?? [];
        }

        /**
         * Shift an element off the start of the Collection
         *
         * @wraps array_shift
         */
        public function shift(&$shifted = null): CollectionInterface
        {
            $inst = static::from($this->items);
            $shifted = \array_shift($inst->items);

            return $inst;
        }

        /**
         * Add arguments to the start of the Collection
         *
         * @wraps array_unshift
         */
        public function unshift(...$args): CollectionInterface
        {
            $inst = static::from($this->items);
            \array_unshift($inst->items, ...$args);

            return $inst;
        }

        /**
         * Shift an element off the end of the Collection
         *
         * @wraps array_pop
         */
        public function pop(&$shifted = null): CollectionInterface
        {
            $inst = static::from($this->items);
            $shifted = \array_pop($inst->items);

            return $inst;
        }

        /**
         * Push one or more elements onto the end of Collection
         *
         * @wraps array_push
         */
        public function push(...$items): CollectionInterface
        {
            \array_push($this->items, ...$items);

            return $this;
        }

        /**
         * Fetches the values
         *
         * @wraps array_values
         */
        public function values(): CollectionInterface
        {
            return static::from(\array_values($this->items));
        }

        /**
         * Fetches the keys
         *
         * @wraps array_keys
         */
        public function keys(): CollectionInterface
        {
            return static::from(\array_keys($this->items));
        }

        /**
         * Exchanges all keys with their associated values
         *
         * @wraps array_flip
         */
        public function flip(): CollectionInterface
        {
            return static::from(array_flip($this->items));
        }

        /**
         * Fetches all unique values
         *
         * @wraps array_unique
         */
        public function unique(): CollectionInterface
        {
            return static::from(array_unique($this->items));
        }

        /**
         * Reverses the order of the items
         *
         * @wraps array_reverse
         */
        public function reverse(bool $preserveKeys = false): CollectionInterface
        {
            return static::from(array_reverse($this->items, $preserveKeys));
        }

        /**
         * Iteratively reduce the array to a single value using a callback function
         *
         * @wraps array_reduce
         */
        public function reduce(callable $callback, $input = [])
        {
            $result = array_reduce(
                \array_keys($this->items),
                fn($t, $i) => $callback($t, $i, $this->items[$i]),
                $input
            );

            return \is_iterable($result)
                ? static::from($result)
                : $result;
        }

        /**
         * Merges the values of multiple iterables into a single Collection
         *
         * @wraps array_merge
         */
        public function merge(iterable ...$sets): CollectionInterface
        {
            return static::from(array_merge($this->items, ...$this->iterableToArray($sets)));
        }

        /**
         * Merges the values of multiple iterables into a single Collection
         *
         * @wraps array_replace
         */
        public function replace(iterable ...$sets): CollectionInterface
        {
            return static::from(array_replace($this->items, ...$this->iterableToArray($sets)));
        }

        /**
         * Merges the values of multiple iterables into a single Collection recursively
         *
         * @wraps array_merge_recursive
         */
        public function mergeRecursive(iterable ...$sets): CollectionInterface
        {
            return static::from(array_merge_recursive($this->items, ...$this->iterableToArray($sets)));
        }

        /**
         * Applies a callback to each item
         *
         * both the key and value are supplied as parameters
         * to the callback, although the return value of the
         * callback  only applies to the value
         *
         * @wraps array_map
         */
        public function map(callable $callback): CollectionInterface
        {
            return static::from(array_map($callback, \array_keys($this->items), \array_values($this->items)));
        }

        /**
         * Pad Collection to the specified length with a value
         *
         * @wraps array_pad
         */
        public function pad(int $size, $value): CollectionInterface
        {
            return static::from(array_pad($this->items, $size, $value));
        }

        /**
         * Find highest value
         *
         * @wraps max
         */
        public function max()
        {
            if(count($this->items) === 0)
            {
                return null;
            }

            return \max($this->items);
        }

        /**
         * Find lowest value
         *
         * @wraps min
         */
        public function min()
        {
            if(count($this->items) === 0)
            {
                return null;
            }

            return \min($this->items);
        }

        public function minFromCallback(callable $callback)
        {
            $v = null;
            $i = null;

            foreach($this->items as $item)
            {
                if(($r = $callback($item)) <= $v || $v === null)
                {
                    $v = $r;
                    $i = $item;
                }
            }

            return $i;
        }

        public function maxFromCallback(callable $callback)
        {
            $v = null;
            $i = null;

            foreach($this->items as $item)
            {
                if(($r = $callback($item)) >= $v || $v === null)
                {
                    $v = $r;
                    $i = $item;
                }
            }

            return $i;
        }

        /**
         * Sums all values
         *
         * @wraps array_sum
         */
        public function sum()
        {
            return array_sum($this->items);
        }

        /**
         * Searches the Collection for a given value and
         * returns the first corresponding key if successful
         *
         * @wraps array_search
         */
        public function search($needle, bool $strict = false): ?int
        {
            $result = array_search($needle, $this->items, $strict);

            return $result === false
                ? null
                : $result;
        }

        /**
         * Applies a callback to each item
         *
         * @wraps array_walk
         */
        public function walk(callable $callback): CollectionInterface
        {
            return static::from(array_walk($this->items, $callback));
        }

        /**
         * Filters through the items
         *
         * the callback is applied to each item and a boolean
         * return value is expected, iterations that return
         * true stay in the Collection, false is removed
         *
         * @wraps array_filter
         */
        public function filter(callable $callback = null, int $option = 0): CollectionInterface
        {
            $args = $callback !== null
                ? [
                    $this->items,
                    $callback,
                    $option,
                ]
                : [
                    $this->items,
                ];

            return static::from(array_filter(...$args));
        }

        /**
         * Filters through the keys of items
         *
         * the callback is applied to each key and a boolean
         * return value is expected, iterations that return
         * true stay in the Collection, false is removed
         */
        public function filterKeys(callable $callback = null): CollectionInterface
        {
            return static::filter($callback, \ARRAY_FILTER_USE_KEY);
        }

        /**
         * Filters through the items
         *
         * the callback is applied to each item and a boolean
         * return value is expected, iterations that return
         * false stay in the Collection, true is removed
         *
         */
        public function reject(callable $callback, int $option = 0): CollectionInterface
        {
            return static::from(\array_filter(
                $this->items,
                fn($v, $k = null) => $callback(...($k === null ? [ $v ] : [ $v, $k ])) === false,
                $option
            ));
        }

        /**
         * Filters through the items, returning both filtered
         * and removed Collections
         *
         */
        public function split(callable $callback, int $option = 0): array
        {
            $res = [ [], [] ];

            foreach($this->items as $key => $value)
            {
                switch($option)
                {
                    case \ARRAY_FILTER_USE_BOTH:
                        $args = [ $key, $value ];
                        break;

                    case \ARRAY_FILTER_USE_KEY:
                        $args = [ $key ];
                        break;

                    default:
                        $args = [ $value ];
                        break;
                }

                $result = $callback(...$args);

                $res[(int)!$result][$key] = $value;
            }

            return [
                static::from($res[0]),
                static::from($res[1]),
            ];
        }

        /**
         * Extract a slice of the array
         *
         * @wraps array_slice
         */
        public function slice(int $start, int $length = null): CollectionInterface
        {
            return static::from(array_slice($this->items, $start, $length, true));
        }

        /**
         * Remove a portion of the array and replace it with
         * something else
         *
         * NOTE
         * With this implementation the extracted
         * values are lost
         *
         * @wraps array_splice
         */
        public function splice(int $start, int $length = null, $replacement = []): CollectionInterface
        {
            return static::from(array_splice($this->items, $start, $length, $replacement));
        }

        /**
         * Computes the difference of iterables
         *
         * @wraps array_diff
         */
        public function diff(iterable ...$sets): CollectionInterface
        {
            return static::from(array_diff($this->items, ...$this->iterableToArray($sets)));
        }

        /**
         * Computes the difference of iterables with
         * additional index check
         *
         * @wraps array_diff_assoc
         */
        public function diffAssoc(iterable ...$sets): CollectionInterface
        {
            return static::from(array_diff_assoc($this->items, ...$this->iterableToArray($sets)));
        }

        /**
         * Computes the difference of iterables using
         * keys for comparison
         *
         * @wraps array_diff_key
         */
        public function diffKey(iterable ...$sets): CollectionInterface
        {
            return static::from(array_diff_key($this->items, ...$this->iterableToArray($sets)));
        }

        /**
         * Computes the difference of iterables by using a callback function for data comparison
         *
         * @wraps array_udiff
         */
        public function uDiff(callable $callback, iterable ...$sets): CollectionInterface
        {
            return static::from(array_udiff($this->items, ...$this->iterableToArray($sets), ...[$callback]));
        }

        /**
         * Computes the difference of iterables with
         * additional index check which is performed
         * by a user supplied callback function
         *
         * @wraps array_diff_uassoc
         */
        public function diffUAssoc(callable $callback, iterable ...$sets): CollectionInterface
        {
            return static::from(array_diff_uassoc($this->items, ...$this->iterableToArray($sets), ...[$callback]));
        }

        /**
         * Computes the difference of iterables using a
         * callback function on the keys for comparison
         *
         * @wraps array_diff_ukey
         */
        public function diffUKey(callable $callback, iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect_ukey($this->items, ...$this->iterableToArray($sets), ...[$callback]));
        }

        /**
         * Computes the difference of iterables
         *
         * @wraps array_intersect
         */
        public function intersect(iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect($this->items, ...$this->iterableToArray($sets)));
        }

        /**
         * Computes the difference of iterables, compares data by a callback function
         *
         * @wraps array_uintersect
         */
        public function uIntersect(callable $callback, iterable ...$sets): CollectionInterface
        {
            return static::from(array_uintersect($this->items, ...$this->iterableToArray($sets), ...[ $callback ]));
        }

        /**
         * Computes the intersection of iterables with
         * additional index check
         *
         * @wraps array_intersect_assoc
         */
        public function intersectAssoc(iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect_assoc($this->items, ...$this->iterableToArray($sets)));
        }

        /**
         * Computes the intersection of iterables using
         * keys for comparison
         *
         * @wraps array_intersect_key
         */
        public function intersectKey(iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect_key($this->items, ...$this->iterableToArray($sets)));
        }

        /**
         * Computes the intersection of iterables with
         * additional index check, compares indexes by
         * a callback function
         *
         * @wraps array_intersect_uassoc
         */
        public function intersectUAssoc(callable $callback, iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect_uassoc($this->items, ...$this->iterableToArray($sets), ...[$callback]));
        }

        /**
         * Computes the intersection of iterables using a
         * callback function on the keys for comparison
         *
         * @wraps array_intersect_ukey
         */
        public function intersectUKey(callable $callback, iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect_ukey($this->items, ...$this->iterableToArray($sets), ...[$callback]));
        }

        /**
         * Applies callback to each item which yields a key => value pair
         *
         * A key => value pair can be one of two thing,
         * either an array or a Generator. in both cases
         * each item the result yields is added as a
         * key => value to the items
         *
         */
        public function each(callable $callback): CollectionInterface
        {
            $collection = [];

            foreach($this->items as $key => $value)
            {
                $result = $callback($key, $value);

                // TODO(Chris Kruining)
                // Implement more methods of
                // transferring key => value pairs
                switch(gettype($result))
                {
                    case 'object':
                        switch(get_class($result))
                        {
                            case \Generator::class:
                                foreach($result as $key => $value)
                                {
                                    $collection[$key] = $value;
                                }

                                break 2;
                        }

                    case 'array':
                        foreach($result as $key => $value)
                        {
                            $collection[$key] = $value;
                        }

                        break;

                    default:
                        break;
                }
            }

            return static::from($collection);
        }

        /**
         * Sort items
         *
         * @lazy-chainable true
         * @wraps sort
         */
        public function sort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->sortCall('sort', $flags);
        }

        /**
         * Sort items in reverse
         *
         * @lazy-chainable true
         * @wraps rsort
         */
        public function rSort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->sortCall('rsort', $flags);
        }

        /**
         * Sorts items and maintain index association
         *
         * @lazy-chainable true
         * @wraps asort
         */
        public function aSort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->sortCall('asort', $flags);
        }

        /**
         * Sort items in reverse and maintain index association
         *
         * @lazy-chainable true
         * @wraps arsort
         */
        public function aRSort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->sortCall('arsort', $flags);
        }

        /**
         * Sort items by key
         *
         * @lazy-chainable true
         * @wraps ksort
         */
        public function kSort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->sortCall('ksort', $flags);
        }

        /**
         * Sort items by key in reverse
         *
         * @lazy-chainable true
         * @wraps krsort
         */
        public function kRSort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->sortCall('krsort', $flags);
        }

        /**
         * Sort items by values using a user-defined comparison function
         *
         * @lazy-chainable true
         * @wraps usort
         */
        public function uSort(callable $callback): CollectionInterface
        {
            return $this->sortCall('usort', $callback);
        }

        /**
         * Sort items with a user-defined comparison function and maintain index association
         *
         * @lazy-chainable true
         * @wraps uasort
         */
        public function uASort(callable $callback): CollectionInterface
        {
            return $this->sortCall('uasort', $callback);
        }

        /**
         * Sort items by keys using a user-defined comparison function
         *
         * @lazy-chainable true
         * @wraps uksort
         */
        public function uKSort(callable $callback): CollectionInterface
        {
            return $this->sortCall('uksort', $callback);
        }

        /**
         * Topologically sort items
         */
        public function topologicalSort(string $edgeKey): CollectionInterface
        {
            $keys = array_fill_keys(array_keys($this->items), 0);
            $values = $this->map(fn($k, $v) => $v[$edgeKey]);

            foreach($values as $value)
            {
                $edges = $value ?? [];

                foreach($edges as $edge)
                {
                    if(key_exists($edge, $keys))
                    {
                        $keys[$edge]++;
                    }
                }
            }

            asort($keys);
            $keys = array_reverse($keys);

            foreach($keys as $key => &$value)
            {
                $value = $this->items[$key];
            }

            unset($value);

            $this->items = $keys;

            return $this;
        }

        /**
         * Returns a power set of all the values
         *
         * NOTE
         * courtesy of https://stackoverflow.com/a/6092999
         */
        public function powerSet(int $minLength = 1): CollectionInterface
        {
            $count = \count($this);
            $members = 2**$count;
            $values = \array_values($this->items);
            $return = [];

            for($i = 0; $i < $members; $i++)
            {
                $b = sprintf("%0" . $count . "b", $i);
                $out = [];

                for($j = 0; $j < $count; $j++)
                {
                    if($b[$j] == '1')
                    {
                        $out[] = $values[$j];
                    }
                }

                if(count($out) >= $minLength)
                {
                    $return[] = $out;
                }
            }

            return static::from($return);
        }

        /**
         * Returns if the collection has any keys that are a string
         */
        public function isAssociative(): bool
        {
            return $this->some(fn($k, $v) => is_string($k));
        }

        /**
         * Get item by index
         *
         * NOTE
         * negative indexes are allowed, i.e. an
         * index of -2 would yield the second last
         * item
         */
        public function byIndex(int $i, bool $key = false)
        {
            $values = $key === false
                ? \array_values($this->items)
                : \array_keys($this->items);
            $key = Arithmetic::Modulus($i, count($values));

            return \key_exists($key, $values)
                ? $values[$key]
                : self::UNDEFINED;
        }

        /**
         * Returns the first item
         */
        public function first(bool $key = false)
        {
            return $this->byIndex(0, $key);
        }

        /**
         * Returns the last item
         */
        public function last(bool $key = false)
        {
            return $this->byIndex(-1, $key);
        }

        /**
         * Split items into chunks
         *
         * @wraps array_chunk
         */
        public function chunk(int $size, bool $preserveKeys = false): CollectionInterface
        {
            return static::from(array_chunk($this->items, $size, $preserveKeys));
        }

        /**
         * Combine another iterable with items
         *
         * uses the items as key and the supplied iterable
         * as values to create a new Collection
         *
         * @wraps array_combine
         */
        public function combine(iterable $values): CollectionInterface
        {
            return static::from(array_combine($this->items, $values instanceof \Traversable
                ? iterator_to_array($values, true)
                : $values
            ));
        }

        /**
         * Loops recursively over the Collection and
         * flattens the structure to a 1-dimensional
         * array
         */
        public function flatten(string $delimiter = '_', string $prefix = ''): \Generator
        {
            foreach($this as $i => $item)
            {
                $key = \strlen($prefix) > 0
                    ? $prefix . $delimiter
                    : '';

                $key .= $i;

                if($item instanceof Collection)
                {
                    yield from $item->flatten($delimiter, $key);
                }
                else
                {
                    yield $key => $item;
                }
            }
        }

        /**
         * Searches for a value in the items via callback
         */
        public function find(callable $callback, bool $returnNull = false)
        {
            foreach($this->items as $item)
            {
                if($callback($item) === true)
                {
                    return $item;
                }
            }

            return $returnNull === true
                ? null
                : self::UNDEFINED;
        }

        /**
         * Searches for an index in the items via callback
         */
        public function findIndex(callable $callback): ?int
        {
            foreach($this->items as $i => $v)
            {
                if($callback($i, $v) === true)
                {
                    return $i;
                }
            }

            return null;
        }

        /**
         * Executes check on every item
         *
         * Iterates over each item applying the callback,
         * which must return true for each iteration for
         * the method to return true
         *
         * NOTE
         */
        public function every(callable $callback): bool
        {
            foreach($this->items as $key => $item)
            {
                if(!$callback($key, $item))
                {
                    return false;
                }
            }

            return true;
        }

        /**
         * Executes check on every item
         *
         * NOTE
         * Breaks on first true return value and returns true
         */
        public function some(callable $callback): bool
        {
            foreach($this->items as $key => $item)
            {
                if($callback($key, $item))
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Checks if Collection contains the given value
         */
        public function includes($value): bool
        {
            return array_search($value, $this->items) !== false;
        }

        /**
         * Checks if each passed key exists in the Collection
         */
        public function has($key, string ...$keys): bool
        {
            $keys = \array_merge([ $key ], $keys);

            return count(array_diff($keys, array_keys($this->items))) === 0;
        }

        /**
         * Checks if any passed key exists in the Collection
         */
        public function hasAny($key, string ...$keys): bool
        {
            \array_unshift($keys, $key);

            return count(array_diff($keys, array_keys($this->items))) < count($keys);
        }

        /**
         * returns the values of the provided keys
         */
        public function get($key, string ...$keys): Resolvable
        {
            $keys = \array_merge([ $key ], $keys);

            if(!$this->has(...$keys))
            {
                throw new NotFound;
            }

            return static::from(\array_intersect_key($this->items, \array_flip($keys)));
        }

        /**
         * sets the value of the given key
         */
        public function set(string $key, $value = self::UNDEFINED): Resolvable
        {
            $this[$key] = $value;

            return $this;
        }

        /**
         * Sanitize iterable to array
         */
        public static function sanitize(iterable $items): array
        {
            return $items instanceof \Traversable
                ? iterator_to_array($items, true)
                : $items;
        }

        /**
         * Create Collection from iterable
         */
        public static function from(iterable $items, bool $lazy = false): CollectionInterface
        {
            $inst = new static;
            $items = static::sanitize($items);

            if($lazy === false)
            {
                // TODO(Chris Kruining)
                // This map makes this method
                // very expensive, maybe split
                // this of into another method
                $items = \array_map(fn($v) => \is_array($v) ? static::from($v) : $v, $items);
            }

            $inst->items = $items;

            return $inst;
        }

        /**
         * Create Collection from JSON string
         */
        public static function fromJson(string $items): CollectionInterface
        {
            return static::from(\json_decode($items, true) ?? []);
        }

        /**
         * Returns the items
         */
        public function toArray() : array
        {
            $self = clone $this;

            \array_walk($self->items, fn(&$i) => $i = $i instanceof CollectionInterface ? $i->toArray() : $i);

            return iterator_to_array(
                $self,
                true
            );
        }

        /**
         * Returns object with the items as properties
         */
        public function toObject() : \stdClass
        {
            return (object)$this->toArray();
        }

        /**
         * Returns string of items joined by delimiter
         *
         * @wraps join
         */
        public function toString(string $delimiter = '', string $format = null): string
        {
            $parts = $this->toArray();

            if($format !== null)
            {
                $parts = \array_map(fn($p) => \sprintf($format, $p), $parts);
            }

            return join($delimiter, $parts);
        }

        /**
         * Create Collection from string exploded by delimiter
         */
        public static function fromString(string $subject, string $delimiter = ' '): CollectionInterface
        {
            return static::from(explode($delimiter, $subject));
        }

        /**
         * Return size of Collection
         */
        public function count(string $key = null): int
        {
            return count($this->items);
        }

        /**
         * Return a Generator
         */
        public function &getIterator(): \Generator
        {
            foreach($this->items as $key => &$value)
            {
                yield $key => $value;
            }

            unset($value);
        }

        /**
         * Returns if the provided offset exists
         */
        public function offsetExists(mixed $offset): bool
        {
            if((\is_string($offset) || \is_numeric($offset)) && \key_exists($offset, $this->items))
            {
                return true;
            }


            switch(\gettype($offset))
            {
                case 'string':
                    $parts = \explode('.', $offset);
                    $container = $this->items;

                    while(($key = \array_shift($parts)) !== null)
                    {
                        if(
                            (\is_array($container) && !\key_exists($key, $container)) ||
                            ($container instanceof CollectionInterface && !$container->has($key))
                        ) {
                            return false;
                        }

                        $container = $container[$key];
                    }

                    return true;

                case 'integer':
                    return key_exists($offset, $this->items);

                default:
                    throw new \Exception(
                        'Unsupported offset type'
                    );
            }
        }

        /**
         * Returns the value by offset
         */
        public function offsetGet(mixed $offset): mixed
        {
            if((\is_string($offset) || \is_numeric($offset)) && \key_exists($offset, $this->items))
            {
                return $this->items[$offset];
            }

            switch(\gettype($offset))
            {
                case 'string':
                    $queries = \array_flip(\preg_split('/\s*,\s*/', $offset));

                    foreach($queries as $query => &$container)
                    {
                        $parts = \explode('.', $query);
                        $container = $this->items;

                        while(($key = \array_shift($parts)) !== null)
                        {
                            if(
                                (\is_array($container) && !\key_exists($key, $container)) ||
                                ($container instanceof CollectionInterface && !$container->has($key))
                            ) {
                                $container = self::UNDEFINED;

                                continue 2;
                            }

                            $container = $container[$key];
                        }
                    }

                    unset($container);

                    $queries = static::from($queries);

                    return $queries->count() === 1
                        ? $queries->first()
                        : $queries;

                case 'integer':
                    return $this->byIndex($offset);

                default:
                    throw new \Exception(
                        'Unsupported offset type'
                    );
            }
        }

        /**
         * Sets value by offset
         */
        public function offsetSet(mixed $offset, mixed $value): void
        {
            switch(\gettype($offset))
            {
                case 'string':
                    $parts = \explode('.', $offset);
                    $container = &$this->items;

                    while(($key = \array_shift($parts)) !== null && \count($parts) > 0)
                    {
                        if(
                            (\is_array($container) && !\key_exists($key, $container)) ||
                            ($container instanceof CollectionInterface && !$container->has($key))
                        ) {
                            $container[$key] = new static;
                        }

                        $container = &$container[$key];
                    }

                    $container[$key] = $value;
                    break;

                case 'integer':
                    $this->items[$offset] = $value;
                    break;

                case 'NULL':
                    $this->items[] = $value;
                    break;

                default:
                    throw new \InvalidArgumentException;
            }
        }

        /**
         * Removes value by offset
         */
        public function offsetUnset(mixed $offset): void
        {
            if($offset === null)
            {
                return;
            }

            unset($this->items[$offset]);
        }

        /**
         * Prepares Collection to be JSON encoded
         */
        public function jsonSerialize(): mixed
        {
            return $this->items ?? [];
        }

        public function resolve(string $key)
        {
            return $this[$key];
        }

        public function enforceKeys(iterable $keys): void
        {
            if($this->has(...$keys) === false)
            {
                $ownKeys = $this->keys();

                throw new KeyMissing($ownKeys->diff($keys), $ownKeys);
            }
        }


        private function iterableToArray(array $iterables): array
        {
            return array_map(function($i) {
                if($i instanceof Collection)
                {
                    return $i->items;
                }

                return $i instanceof \Traversable
                    ? iterator_to_array($i)
                    : $i;
            }, $iterables);
        }

        private function sortCall(string $function, ...$arguments): CollectionInterface
        {
            $function($this->items, ...$arguments);

            return $this;
        }
    }
}
