<?php

namespace CPB\Utilities\Common
{
    use CPB\Utilities\Contracts\Queryable;
    use CPB\Utilities\Enums\JoinStrategy;
    use CPB\Utilities\Enums\SortDirection;
    use CPB\Utilities\Math\Arithmetic;
    use Paynl\Result\Result;
    
    class Collection implements CollectionInterface
    {
        protected
            $items,
            $lazy = false,
            $chain = []
        ;
        
        protected const
            ITEMS = '__ITEMS__'
        ;
        
        private
            $groupKey
        ;
        
        public function __construct()
        {
            $this->items = [];
        }
        
        public function __clone()
        {
            return static::from($this->items);
        }
        
        public function __toString(): string
        {
            return $this->toString();
        }
        
        /**
         * Executes lazy chain
         *
         * @lazy-chainable false
         */
        public function end(...$args)
        {
            if($this->lazy === false)
            {
                throw new \Exception(
                    'Collection is not in lazy mode'
                );
            }
            
            $items = $this->items;
            
            foreach($this->chain as [ $function, $args])
            {
                foreach($args as &$arg)
                {
                    if($arg === self::ITEMS)
                    {
                        $arg = $items;
                    }
                }
                
                $items = $function(...$args);
            }
            
            $this->chain = [];
            $this->lazy = false;
            
            return static::from($items);
        }
        
        public function __debugInfo(): array
        {
            return $this->items;
        }
        
        /**
         * Sets the collection into lazy mode
         *
         * A Collection that is in lazy mode will store all
         * its method calls in a buffer to be executed when
         * thew Collection is invoked
         *
         * @lazy-chainable false
         */
        public function lazy(): CollectionInterface
        {
            $this->lazy = true;
            
            // TODO(Chris Kruining)
            // Check if the chain
            // actually needs to be
            // cleared here
            $this->chain = [];
            
            return $this;
        }
        
        /**
         * Fetches the values
         *
         * @lazy-chainable true
         * @wraps array_values
         */
        public function values(): CollectionInterface
        {
            return $this->chainOrExecute('array_values', self::ITEMS);
        }
        
        /**
         * Fetches the keys
         *
         * @lazy-chainable true
         * @wraps array_keys
         */
        public function keys(): CollectionInterface
        {
            return $this->chainOrExecute('array_keys', self::ITEMS);
        }
        
        /**
         * Fetches all unique values
         *
         * @lazy-chainable true
         * @wraps array_unique
         */
        public function unique(): CollectionInterface
        {
            return $this->chainOrExecute('array_unique', self::ITEMS);
        }
        
        /**
         * Reverses the order of the items
         *
         * @lazy-chainable true
         * @wraps array_reverse
         */
        public function reverse(bool $preserveKeys = false): CollectionInterface
        {
            return $this->chainOrExecute('array_reverse', self::ITEMS, $preserveKeys);
        }
        
        /**
         * Iteratively reduce the array to a single value using a callback function
         *
         * @lazy-chainable false
         * @wraps array_reduce
         */
        public function reduce(callable $callback, $input = null)
        {
            $result = array_reduce(
                    \array_keys($this->items),
                    function($t, $i) use($callback){ return $callback($t, $i, $this->items[$i]); },
                    $input ?? []
                ) ?? [];
            
            return \is_iterable($result) ?
                static::from($result)
                : $result;
        }
        
        /**
         * Merges the values of multiple iterables into a single Collection
         *
         * @lazy-chainable false
         * @wraps array_merge
         */
        public function merge(iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute('array_merge', self::ITEMS, ...$this->iterableToArray($sets));
        }
        
        /**
         * Applies a callback to each item
         *
         * both the key and value are supplied as parameters
         * to the callback, although the return value of the
         * callback  only applies to the value
         *
         * @lazy-chainable true
         * @wraps array_map
         */
        public function map(callable $callback): CollectionInterface
        {
            return $this->chainOrExecute(
                function(array $items) use($callback){
                    return \array_map($callback, \array_keys($items), \array_values($items));
                },
                self::ITEMS
            );
        }
        
        /**
         * Applies a callback to each item
         *
         * @lazy-chainable true
         * @wraps array_walk
         */
        public function walk(callable $callback): CollectionInterface
        {
            return $this->chainOrExecute(
                function(array &$items) use($callback){
                    \array_walk($items, $callback);
                    
                    return $items;
                },
                self::ITEMS
            );
        }
        
        /**
         * Filters through the items
         *
         * the callback is applied to each item and a boolean
         * return value is expected, iterations that return
         * true stay in the Collection, false is removed
         *
         * @lazy-chainable true
         * @wraps array_filter
         */
        public function filter(callable $callback = null, int $option = 0): CollectionInterface
        {
            $args = $callback !== null
                ? [
                    self::ITEMS,
                    $callback,
                    $option,
                ]
                : [
                    self::ITEMS,
                ];
            
            return $this->chainOrExecute('array_filter', ...$args);
        }
        
        /**
         * Filters through the items, returning both filtered
         * and removed Collections
         *
         * @lazy-chainable false
         */
        public function split(callable $callback, int $option = 0, bool $assoc = false): array
        {
            $filtered = $this->filter($callback, $option);
            
            return [
                $filtered,
                $this->{'diff' . ($assoc ? 'Assoc' : '')}($filtered->toArray()),
            ];
        }
        
        /**
         * Extract a slice of the array
         *
         * @lazy-chainable true
         * @wraps array_slice
         */
        public function slice(int $start, int $length = null): CollectionInterface
        {
            return $this->chainOrExecute('array_slice', self::ITEMS, $start, $length, true);
        }
        
        /**
         * Remove a portion of the array and replace it with
         * something else
         *
         * NOTE
         * With this implementation the extracted
         * values are lost
         *
         * @lazy-chainable true
         * @wraps array_splice
         */
        public function splice(int $start, int $length = null, $replacement = []): CollectionInterface
        {
            return $this->chainOrExecute('array_splice', self::ITEMS, $start, $length, true);
        }
        
        /**
         * Computes the difference of iterables
         *
         * @lazy-chainable true
         * @wraps array_diff
         */
        public function diff(iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute('array_diff', self::ITEMS, ...$this->iterableToArray($sets));
        }
        
        /**
         * Computes the difference of iterables with
         * additional index check
         *
         * @lazy-chainable true
         * @wraps array_diff_assoc
         */
        public function diffAssoc(iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute('array_diff_assoc', self::ITEMS, ...$this->iterableToArray($sets));
        }
        
        /**
         * Computes the difference of iterables using
         * keys for comparison
         *
         * @lazy-chainable true
         * @wraps array_diff_key
         */
        public function diffKey(iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute('array_diff_key', self::ITEMS, ...$this->iterableToArray($sets));
        }
        
        /**
         * Computes the difference of iterables with
         * additional index check which is performed
         * by a user supplied callback function
         *
         * @lazy-chainable true
         * @wraps array_diff_uassoc
         */
        public function diffUAssoc(callable $callback, iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute(
                'array_diff_uassoc',
                self::ITEMS,
                ...$this->iterableToArray($sets),
                ...[$callback] // #LAME
            );
        }
        
        /**
         * Computes the difference of iterables using a
         * callback function on the keys for comparison
         *
         * @lazy-chainable true
         * @wraps array_diff_ukey
         */
        public function diffUKey(callable $callback, iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute(
                'array_intersect_ukey',
                self::ITEMS,
                ...$this->iterableToArray($sets),
                ...[$callback] // #LAME
            );
        }
        
        /**
         * Computes the difference of iterables
         *
         * @lazy-chainable true
         * @wraps array_intersect
         */
        public function intersect(iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute('array_intersect', self::ITEMS, ...$this->iterableToArray($sets));
        }
        
        /**
         * Computes the intersection of iterables with
         * additional index check
         *
         * @lazy-chainable true
         * @wraps array_intersect_assoc
         */
        public function intersectAssoc(iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute('array_intersect_assoc', self::ITEMS, ...$this->iterableToArray($sets));
        }
        
        /**
         * Computes the intersection of iterables using
         * keys for comparison
         *
         * @lazy-chainable true
         * @wraps array_intersect_key
         */
        public function intersectKey(iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute('array_intersect_key', self::ITEMS, ...$this->iterableToArray($sets));
        }
        
        /**
         * Computes the intersection of iterables with
         * additional index check, compares indexes by
         * a callback function
         *
         * @lazy-chainable true
         * @wraps array_intersect_uassoc
         */
        public function intersectUAssoc(callable $callback, iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute(
                'array_intersect_uassoc',
                self::ITEMS,
                ...$this->iterableToArray($sets),
                ...[$callback] // #LAME
            );
        }
        
        /**
         * Computes the intersection of iterables using a
         * callback function on the keys for comparison
         *
         * @lazy-chainable true
         * @wraps array_intersect_ukey
         */
        public function intersectUKey(callable $callback, iterable ...$sets): CollectionInterface
        {
            return $this->chainOrExecute(
                'array_intersect_ukey',
                self::ITEMS,
                ...$this->iterableToArray($sets),
                ...[$callback] // #LAME
            );
        }
        
        /**
         * Applies callback to each item which yields a key => value pair
         *
         * A key => value pair can be one of two thing,
         * either an array or a Generator. in both cases
         * each item the result yields is added as a
         * key => value to the items
         *
         * @lazy-chainable true
         */
        public function each(callable $callback): CollectionInterface
        {
            return $this->chainOrExecute(function($items) use($callback){
                $collection = [];
                
                foreach($items as $key => $value)
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
                
                return $collection;
            }, self::ITEMS);
        }
        
        /**
         * Appends an iterable to the items
         *
         * the keys in the supplied iterable are de-duplicated,
         * duplicate keys are suffixed with '__%i', where %i is
         * the amount of times the key already has occurred
         *
         * @lazy-chainable true
         */
        public function append(iterable $data): CollectionInterface
        {
            $keys = $this->keys();
            
            foreach($data as $key => $value)
            {
                $count = $keys->filter(
                    function($v) use($key){ return count(Regex::match('/^' . $key . '(\(\d\))?/', $v)) > 0; }
                )->count();
                
                if($count > 0)
                {
                    $key .= '__(' . $count . ')';
                }
                
                $this[$key] = $value;
            }
            
            return $this;
        }
        
        /**
         * Searches for a value in the items via callback
         *
         * @lazy-chainable false
         */
        public function find(callable $callback)
        {
            return $this->filter($callback)->first();
        }
        
        /**
         * Executes check on every item
         *
         * Iterates over each item applying the callback,
         * which must return true for each iteration for
         * the method to return true
         *
         * NOTE
         * Breaks on first false return value and returns false
         *
         * @lazy-chainable false
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
         *
         * @lazy-chainable false
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
         *
         * @lazy-chainable false
         */
        public function contains($value): bool
        {
            return array_search($value, $this->items) !== false;
        }
        
        /**
         * Checks if each passed key exists in the Collection
         *
         * @lazy-chainable false
         */
        public function has($key, string ...$keys): bool
        {
            $keys = \array_merge([ $key ], $keys);
            
            return count(array_diff($keys, array_keys($this->items))) === 0;
        }
        
        /**
         * returns the values of the provided keys
         *
         * @lazy-chainable true
         */
        public function get($key, string ...$keys): CollectionInterface
        {
            $keys = \array_merge([ $key ], $keys);
            
            if(!$this->has(...$keys))
            {
                throw new NotFoundException;
            }
            
            return $this->filter(
                function($k) use($keys){ return in_array($k, $keys); },
                ARRAY_FILTER_USE_KEY
            );
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
         *
         * @lazy-chainable true
         */
        public function topologicalSort(string $edgeKey): CollectionInterface
        {
            $keys = array_fill_keys(array_keys($this->items), 0);
            
            foreach($this->select($edgeKey) as $key => $value)
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
            
            $this->items = $keys;
            
            return $this;
        }
        
        /**
         * Returns a power set of all the values
         *
         * NOTE
         * courtesy of https://stackoverflow.com/a/6092999
         *
         * @lazy-chainable true
         */
        public function powerSet(int $minLength = 1): CollectionInterface
        {
            return $this->chainOrExecute(function($items) use($minLength){
                $count = \count($this);
                $members = 2**$count;
                $values = \array_values($items);
                $return = [];
    
                for($i = 0; $i < $members; $i++)
                {
                    $b = sprintf("%0" . $count . "b", $i);
                    $out = [];
        
                    for($j = 0; $j < $count; $j++)
                    {
                        if($b{$j} == '1')
                        {
                            $out[] = $values[$j];
                        }
                    }
        
                    if(count($out) >= $minLength)
                    {
                        $return[] = $out;
                    }
                }
    
                return $return;
            }, self::ITEMS);
        }
        
        /**
         * Returns if the collection has any keys that are a string
         *
         * @lazy-chainable false
         */
        public function isAssociative(): bool
        {
            return $this->some(function($k, $v) { return is_string($k); });
        }
        
        /**
         * Get item by index
         *
         * NOTE
         * negative indexes are allowed, i.e. an
         * index of -2 would yield the second last
         * item
         *
         * @lazy-chainable true
         */
        public function byIndex(int $i)
        {
            $values = $this->values();
            
            return $values[Arithmetic::Modulus($i, count($values))] ?? null;
        }
        
        /**
         * Returns the first item
         *
         * @lazy-chainable false
         */
        public function first()
        {
            return reset($this->items);
        }
        
        /**
         * Returns the last item
         *
         * @lazy-chainable false
         */
        public function last()
        {
            return reset(\array_reverse($this->items));
        }
        
        /**
         * Split items into chunks
         *
         * @lazy-chainable true
         * @wraps array_chunk
         */
        public function chunk(int $size, bool $preserveKeys = false): CollectionInterface
        {
            return $this->chainOrExecute('array_chunk', self::ITEMS, $size, $preserveKeys);
        }
        
        /**
         * Combine another iterable with items
         *
         * uses the items as key and the supplied iterable
         * as values to create a new Collection
         *
         * @lazy-chainable true
         * @wraps array_combine
         */
        public function combine(iterable $values): CollectionInterface
        {
            $values = $values instanceof \Traversable
                ? iterator_to_array($values, true)
                : $values;
            
            return $this->chainOrExecute('array_combine', self::ITEMS, $values);
        }
        
        /**
         * Create Collection from iterable
         *
         * @lazy-chainable false
         */
        public static function from(iterable $items): CollectionInterface
        {
            $inst = new static;
            $items = $items instanceof \Traversable
                ? iterator_to_array($items, true)
                : $items;
            
            \array_walk($items, function(&$v) { $v = \is_array($v) ? static::from($v) : $v; });
            
            $inst->items = $items;
            
            return $inst;
        }
        
        /**
         * Returns the items
         *
         * @lazy-chainable false
         */
        public function toArray() : array
        {
            \array_walk($this->items, function(&$i){ $i = $i instanceof static ? $i->toArray() : $i; });
            
            return iterator_to_array(
                $this,
                true
            );
        }
        
        /**
         * Returns object with the items as properties
         *
         * @lazy-chainable false
         */
        public function toObject() : \stdClass
        {
            return (object)$this->toArray();
        }
        
        /**
         * Returns string of items joined by delimiter
         *
         * @lazy-chainable false
         * @wraps join
         */
        public function toString(string $delimiter = ''): string
        {
            return join($delimiter, $this->toArray());
        }
        
        /**
         * Create Collection from string exploded by delimiter
         *
         * @lazy-chainable false
         */
        public static function fromString(string $subject, string $delimiter = ' '): CollectionInterface
        {
            return static::from(explode($delimiter, $subject));
        }
        
        /**
         * Return size of Collection
         *
         * @lazy-chainable false
         */
        public function count(): int
        {
            return count($this->items);
        }
        
        /**
         * Return a Generator
         *
         * @lazy-chainable false
         */
        public function getIterator(): \Generator
        {
            yield from $this->items;
        }
        
        /**
         * Queries over the items
         *
         * @lazy-chainable false
         */
        public function select($query)
        {
            $resolver = function($row, $q) use(&$resolver) {
                $keys = Collection::from(Regex::split('/,\s*(?![^()]*(?:\([^()]*\))?\))/', $q, -1, PREG_SPLIT_NO_EMPTY));
                
                return $keys->each(function($k, $f) use($row, &$resolver){
                    $function = Regex::match('/(.*?)\((.*)\)/', $f);
                    
                    if(count($function) > 0)
                    {
                        // TODO(Chris Kruining)
                        // Implement external parser
                        
                        if(method_exists($this, $function[1]))
                        {
                            $row = $this->{$function[1]}(...explode(',', $function[2]));
                        }
                        else
                        {
                            $row = null;
                        }
                    }
                    else
                    {
                        $keys = explode('.', $f);
                        
                        while(($key = array_shift($keys)) !== null && $row !== null)
                        {
                            if($key === '*')
                            {
                                $row = $row instanceof CollectionInterface
                                    ? $row->values()
                                    : array_values($row);
                            }
                            else
                            {
                                $map = function($k, $v) use($key){ return $v[$key] ?? null; };
                                $filter = function($v) { return $v !== null; };
                                
                                $row = $row[$key] ?? ($row instanceof CollectionInterface
                                        ? $row->map($map)->filter($filter)
                                        : array_filter(
                                            array_map($map, \array_keys($row), \array_values($row)),
                                            $filter
                                        )
                                    ) ?? null;
                            }
                        }
                        
                    }
                    
                    yield $f => $row;
                })
                    ->toArray();
            };
            
            $result = Collection::from($resolver($this->items, $query))
                ->map(function($k, $v) {
                    return is_iterable($v) && count($v) === 1
                        ? array_values($v)[0]
                        : $v;
                });
            
            $result = $result->count() > 1
                ? $result
                : $result->first();
            
            return \is_iterable($result) && !$result instanceof self
                ? static::from($result)
                : $result;
        }
        
        /**
         * Inserts value into the Items based on provided query
         *
         * @lazy-chainable false
         */
        public function insert(string $query, $value): Collection
        {
            $keys = explode('.', $query);
            $row = &$this->items;
            
            while(($key = array_shift($keys)) !== null && $row !== null)
            {
                if(\is_array($row) && !\key_exists($key, $row))
                {
                    $row[$key] = new static;
                }
                elseif($row instanceof static && !$row->has($key))
                {
                    $row->items[$key] = new static;
                }
    
                $row = &$row[$key];
            }
            
            if(is_iterable($row))
            {
                $row[] = $value;
            }
            else
            {
                $row = $value;
            }
            
            return $this;
        }
        
        /**
         * Filters items
         *
         * @lazy-chainable true
         * @alias filter
         */
        public function where($expression = ''): Queryable
        {
            // TODO(Chris Kruining)
            // Implement method
            return $this->filter(function($v) { return true; });
        }
        
        /**
         * Executes a mysql'esc JOIN on the Collection
         *
         * @lazy-chainable true
         */
        public function join(
            iterable $iterable,
            string $localKey,
            string $foreignKey,
            JoinStrategy $strategy = null
        ): Queryable
        {
            $iterable = static::from($iterable)->map(function($k, $v){
                return array_combine(
                    array_map(
                        function($key) {
                            return 'right' . $key;
                        },
                        array_keys($v)
                    ),
                    $v
                );
            })->toArray();
            $foreignKey = 'right' . $foreignKey;
            
            $leftIndex = array_map(function($row) use($localKey){ return $row[$localKey]; }, $this->items);
            $rightIndex = array_map(function($row) use($foreignKey){ return $row[$foreignKey]; }, $iterable);
            $matchedIndexes = array_map(function($v) use ($rightIndex){ return array_search($v, $rightIndex); }, array_intersect($leftIndex, $rightIndex));
            
            switch($strategy ?? JoinStrategy::INNER)
            {
                // both collections need to have a matching value
                case Queryable::JOIN_INNER:
                    $result = array_map(function($k, $v) use($iterable) {
                        return array_merge(
                            $this->items[$k],
                            $iterable[$v]
                        );
                    }, array_keys($matchedIndexes), $matchedIndexes);
                    
                    break;
                // all rows from both collections and intersect matching rows
                case Queryable::JOIN_OUTER:
                    $result = [];
                    $usedIndexes = [];
                    
                    foreach($this->items as $i => $row)
                    {
                        if(key_exists($i, $matchedIndexes))
                        {
                            $usedIndexes[] = $matchedIndexes[$i];
                            
                            $right = $iterable[$matchedIndexes[$i]];
                        }
                        
                        $result[] = array_merge(
                            $row,
                            $right ?? []
                        );
                    }
                    
                    $result = array_merge($result, array_filter($iterable, function($i) use($usedIndexes){ return !in_array($i, $usedIndexes); }, ARRAY_FILTER_USE_KEY));
                    
                    break;
                // all rows from left collection and intersect matching rows
                case Queryable::JOIN_LEFT:
                    $result = [];
                    
                    foreach($this->items as $i => $row)
                    {
                        $result[] = array_merge(
                            $row,
                            $iterable[$matchedIndexes[$i] ?? -1] ?? []
                        );
                    }
                    
                    break;
                // all rows from right collection and intersect matching rows
                case Queryable::JOIN_RIGHT:
                    $result = [];
                    
                    $matchedIndexes = array_flip($matchedIndexes);
                    
                    foreach($iterable as $i => $row)
                    {
                        $result[] = array_merge(
                            $this->items[$matchedIndexes[$i] ?? -1] ?? [],
                            $row
                        );
                    }
                    
                    break;
            }
            
            return static::from($result);
        }
        
        /**
         * Return sub-selection of items
         *
         * @lazy-chainable true
         * @alias slice
         */
        public function limit(int $length): Queryable
        {
            return $this->slice(0, $length);
        }
        
        /**
         * Return sub-selection of items
         *
         * @lazy-chainable true
         * @alias slice
         */
        public function offset(int $start): Queryable
        {
            return $this->slice($start, null);
        }
        
        /**
         * Executes a mysql'esc UNION on Collction
         *
         * @lazy-chainable true
         * @alias merge
         */
        public function union(iterable $iterable): Queryable
        {
            return static::from(array_merge($this->items, Collection::from($iterable)->toArray()));
        }
        
        /**
         * Fetches all unique values of provided key
         *
         * @lazy-chainable true
         */
        public function distinct(string $key): Queryable
        {
            return static::from(array_unique(array_map(function($v) use($key){ return $v[$key]; }, $this->items)));
        }
        
        /**
         * Sort items by provided key and direction
         *
         * @lazy-chainable true
         */
        public function order(string $key, SortDirection $direction = null): Queryable
        {
            return $this->uASort(function($a, $b) use($key, $direction){
                if($direction ?? SortDirection::ASC === SortDirection::DESC)
                {
                    [$b, $a] = [$a, $b];
                }
                
                return $a[$key] ?? null <=> $b[$key] ?? null;
            });
        }
        
        /**
         * Prepares the groups of later queries
         *
         * This method sets the key by which the results
         * of: sum, average, max, min and clamp, will
         * group by
         *
         * @lazy-chainable true
         */
        public function group(string $key): Queryable
        {
            $this->groupKey = $key;
            
            return $this;
        }
        
        /**
         * Sums all values by key
         *
         * @lazy-chainable false
         */
        public function sum(string $key = null)
        {
            return $this->columnAction('array_sum', $key ?? '');
        }
        
        /**
         * Averages all values by key
         *
         * @lazy-chainable false
         */
        public function average(string $key)
        {
            return $this->columnAction(function($arr){ return array_sum($arr) / count($arr); }, $key ?? '');
        }
        
        /**
         * Returns highest value by key
         *
         * optionally a upper bound can be set
         *
         * @lazy-chainable false
         */
        public function max(string $key, float $limit = null)
        {
            $result = $this->columnAction('max', $key);
            
            if($limit !== null)
            {
                if($result instanceof static)
                {
                    $result = $result->map(function($k, $v) use($limit){ return min($v, $limit); });
                }
                else
                {
                    $result = min($result, $limit);
                }
            }
            
            return $result;
        }
        
        /**
         * Returns lowest value by key
         *
         * optionally a lower bound can be set
         *
         * @lazy-chainable false
         */
        public function min(string $key, float $limit = null)
        {
            $result = $this->columnAction('min', $key);
            
            if($limit !== null)
            {
                if($result instanceof static)
                {
                    $result = $result->map(function($k, $v) use($limit){ return max($v, $limit); });
                }
                else
                {
                    $result = max($result, $limit);
                }
            }
            
            return $result;
        }
        
        /**
         * Returns value by key with lower and upper bound
         *
         * @lazy-chainable false
         */
        public function clamp(string $key, float $lower, float $upper)
        {
            $result = $this->min($key, $lower);
            $map = function($k, $v) use($upper){ return min($v, $upper); };
            
            if($result instanceof static)
            {
                return $result->map($map);
            }
            else
            {
                return $map(null, $result);
            }
        }
        
        /**
         * Returns if the provided offset exists
         *
         * @lazy-chainable false
         */
        public function offsetExists($offset): bool
        {
            return key_exists($offset, $this->items);
        }
        
        /**
         * Returns the value by offset
         *
         * @lazy-chainable false
         */
        public function offsetGet($offset)
        {
            switch(\gettype($offset))
            {
                case 'string':
                    return $this->items[$offset] ?? $this->select($offset);
                
                case 'integer':
                    return $this->items[$offset] ?? $this->byIndex($offset);
                
                default:
                    throw new \Exception(
                        'Unsupported offset type'
                    );
            }
        }
        
        /**
         * Sets value by offset
         *
         * @lazy-chainable false
         */
        public function offsetSet($offset, $value)
        {
            if($offset === null)
            {
                $this->items[] = $value;
            }
            elseif(strpos($offset, '.') !== false)
            {
                $this->items[$offset] = $value;
            }
            else
            {
                return $this->insert($offset, $value);
            }
        }
        
        /**
         * Removes value by offset
         *
         * @lazy-chainable false
         */
        public function offsetUnset($offset)
        {
            unset($this->items[$offset]);
        }
        
        /**
         * Serializes Collection to JSON string
         *
         * @lazy-chainable false
         */
        public function serialize(): string
        {
            return json_encode($this->ToArray());
        }
        
        /**
         * Create Collection from JSON string
         *
         * @lazy-chainable false
         */
        public function unserialize($serialized): Collection
        {
            return static::From(json_decode($serialized, true));
        }
        
        /**
         * Prepares Collection to be JSON encoded
         *
         * @lazy-chainable false
         */
        public function jsonSerialize(): array
        {
            return $this->ToArray();
        }
        
        
        private function iterableToArray(array $iterables): array
        {
            return array_map(function($i) {
                return $i instanceof \Traversable
                    ? iterator_to_array($i)
                    : $i;
            }, $iterables);
        }
        
        private function columnAction(callable $method, string $key, ...$args)
        {
            if($this->groupKey === null)
            {
                $items = $this->select($key);
                
                switch(true)
                {
                    case $items instanceof static:
                        $items = $items->toArray();
                        break;
                        
                    case !\is_array($items):
                        $items = [ $items ];
                        break;
                }
                
                return $method($items, ...$args);
            }
            else
            {
                return $this->chainOrExecute(function() use($method, $key, $args){
                    $groups = $this->distinct($this->groupKey)->toArray();
                    $results = [];
                    
                    foreach($groups as $group)
                    {
                        $set = $this->filter(function($v) use($group){ return $v[$this->groupKey] === $group; });
                        $results[] = $method($set->select($key)->toArray(), ...$args);
                    }
                    
                    return $results;
                });
            }
        }
        
        private function sortCall(string $function, ...$arguments): CollectionInterface
        {
            return $this->chainOrExecute(function(&$items) use($function, $arguments){
                $function($items, ...$arguments);
                
                return $items;
            }, self::ITEMS);
        }
        
        private function chainOrExecute(callable $function, ...$args)
        {
            if($this->lazy === true)
            {
                $this->chain[] = [
                    $function,
                    $args,
                ];
                
                return $this;
            }
            else
            {
                foreach($args as &$arg)
                {
                    if($arg === self::ITEMS)
                    {
                        $arg = $this->items;
                    }
                }
                
                return static::from($function(...$args));
            }
        }
    }
}
