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
        protected $items;
        private $groupKey;

        public function __construct()
        {
            $this->items = [];
        }

        public function __call($method, $parameters)
        {
            $function = 'array' . preg_replace_callback(
                    '/[A-Z]/',
                    function($match) {
                        return '_' . strtolower($match[0]);
                    },
                    $method
                );

            // TODO(Chris Kruining)
            // Find a way to reference the
            // collection instance from a
            // callable without changing the
            // $this argument as this is
            // considered bad practice
            $parameters = array_map(function($parameter){
                if($parameter instanceof CollectionInterface)
                {
                    return $parameter->toArray();
                }
                
                return $parameter;
            }, $parameters);

            if(function_exists($function))
            {
                $result = $function($this->items, ...$parameters);

                return is_array($result)
                    ? static::from($result)
                    : $result;
            }

            $function = strtolower($method);

            if(strpos($function, 'sort') !== false && function_exists($function))
            {
                $function($this->items, ...$parameters);

                return $this;
            }
        }

        public function __clone()
        {
            return static::from($this->items);
        }

        public function __toString(): string
        {
            return $this->toString();
        }

        public function __debugInfo(): array
        {
            return $this->items;
        }

        public function values(): CollectionInterface
        {
            return static::from(array_values($this->items));
        }

        public function keys(): CollectionInterface
        {
            return static::from(array_keys($this->items));
        }

        public function unique(): CollectionInterface
        {
            return static::from(array_unique($this->items));
        }

        public function reverse(bool $preserveKeys = false): CollectionInterface
        {
            return static::from(array_reverse($this->items, $preserveKeys));
        }

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

        public function merge(iterable ...$sets): CollectionInterface
        {
            return static::from(array_merge($this->items, ...$this->iterableToArray($sets)));
        }

        public function map(callable $callback): CollectionInterface
        {
            return static::from(
                array_map(
                    $callback,
                    array_keys($this->items),
                    array_values($this->items)
                )
            );
        }

        public function filter(callable $callback = null, int $option = 0): CollectionInterface
        {
            if($callback !== null)
            {
                $args = [
                    $this->items,
                    $callback,
                    $option,
                ];
            }
            else
            {
                $args = [
                    $this->items,
                ];
            }

            return static::from(array_filter(...$args));
        }

        public function split(callable $callback, int $option = 0, bool $assoc = false): array
        {
            $filtered = $this->filter($callback, $option);

            return [
                $filtered,
                $this->{'diff' . ($assoc ? 'Assoc' : '')}($filtered->toArray()),
            ];
        }

        public function slice(int $start, int $length = null): CollectionInterface
        {
            return static::from(array_slice($this->items, $start, $length, true));
        }

        // NOTE(Chris Kruining)
        // With this implementation
        // the extracted values are lost
        public function splice(int $start, int $length = null, $replacement = []): CollectionInterface
        {
            // NOTE(Chris Kruining)
            // Explicitly assign
            // items so that the
            // array is copied
            $items = $this->items;

            array_splice($items, $start, $length, true);

            return static::from($items);
        }

        public function diff(iterable ...$sets): CollectionInterface
        {
            return static::from(array_diff(
                $this->items,
                ...$this->iterableToArray($sets)
            ));
        }

        public function diffAssoc(iterable ...$sets): CollectionInterface
        {
            return static::from(array_diff_assoc(
                $this->items,
                ...$this->iterableToArray($sets)
            ));
        }

        public function diffKey(iterable ...$sets): CollectionInterface
        {
            return static::from(array_diff_key(
                $this->items,
                ...$this->iterableToArray($sets)
            ));
        }

        public function diffUAssoc(callable $callback, iterable ...$sets): CollectionInterface
        {
            return static::from(array_diff_key(
                $this->items,
                ...$this->iterableToArray($sets),
                ...[$callback] // #LAME
            ));
        }

        public function diffUKey(callable $callback, iterable ...$sets): CollectionInterface
        {
            return static::from(array_diff_key(
                $this->items,
                ...$this->iterableToArray($sets),
                ...[$callback] // #LAME
            ));
        }

        public function intersect(iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect(
                $this->items,
                ...$this->iterableToArray($sets)
            ));
        }

        public function intersectAssoc(iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect_assoc(
                $this->items,
                ...$this->iterableToArray($sets)
            ));
        }

        public function intersectKey(iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect_key(
                $this->items,
                ...$this->iterableToArray($sets)
            ));
        }

        public function intersectUAssoc(callable $callback, iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect_uassoc(
                $this->items,
                ...$this->iterableToArray($sets),
                ...[$callback] // #LAME
            ));
        }

        public function intersectUKey(callable $callback, iterable ...$sets): CollectionInterface
        {
            return static::from(array_intersect_ukey(
                $this->items,
                ...$this->iterableToArray($sets),
                ...[$callback] // #LAME
            ));
        }

        // NOTE(Chris Kruining)
        // This function is meant to
        // take an action on each item
        // in the collection allowing
        // to change key and value,
        // whereas Map only allows for
        // changes in the value
        public function each(callable $callback): CollectionInterface
        {
            $collection = new static;

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

            return $collection->count() === 0 ? $this : $collection;
        }

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

        public function find(callable $callback)
        {
            return $this->filter($callback)->first();
        }

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

        public function contains($value): bool
        {
            return array_search($value, $this->items) !== false;
        }

        public function has($key, string ...$keys): bool
        {
            $keys = \array_merge([ $key ], $keys);

            return count(array_diff($keys, array_keys($this->items))) === 0;
        }

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

        public function sort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->sortCall('sort', $flags);
        }

        public function rSort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->sort($flags)->reverse();
        }

        public function aSort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->sortCall('asort', $flags);
        }

        public function aRSort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->aSort($flags)->reverse();
        }

        public function kSort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->sortCall('ksort', $flags);
        }

        public function kRSort(int $flags = SORT_REGULAR): CollectionInterface
        {
            return $this->kSort($flags)->reverse();
        }

        public function uSort(callable $callback): CollectionInterface
        {
            return $this->sortCall('usort', $callback);
        }

        public function uASort(callable $callback): CollectionInterface
        {
            return $this->sortCall('uasort', $callback);
        }

        public function uKSort(callable $callback): CollectionInterface
        {
            return $this->sortCall('uksort', $callback);
        }

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

        // NOTE(Chris Kruining)
        // courtesy of https://stackoverflow.com/a/6092999
        public function powerSet(int $minLength = 1): CollectionInterface
        {
            $count = $this->count();
            $members = pow(2, $count);
            $values = $this->values();
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

            return static::from($return);
        }

        public function isAssociative(): bool
        {
            return $this->some(function($k, $v) { return is_string($k); });
        }

        public function byIndex(int $i)
        {
            $values = $this->values();

            return $values[Arithmetic::Modulus($i, count($values))] ?? null;
        }

        public function first()
        {
            return reset($this->items);
        }

        public function last()
        {
            return reset(\array_reverse($this->items));
        }

        public function chunk(int $size, bool $preserveKeys = false): CollectionInterface
        {
            return static::from(array_chunk($this->items, $size, $preserveKeys));
        }

        public function combine(iterable $values): CollectionInterface
        {
            $values = $values instanceof \Traversable
                ? iterator_to_array($values, true)
                : $values;

            return static::from(array_combine($this->items, $values));
        }

        public static function from(iterable $items): CollectionInterface
        {
            $inst = new static;
            $inst->items = $items instanceof \Traversable
                ? iterator_to_array($items, true)
                : $items;

            return $inst;
        }

        public function toArray() : array
        {
            return iterator_to_array($this, true);
        }

        public function toObject() : \stdClass
        {
            return (object)$this->ToArray();
        }

        public function toString(string $delimiter = ''): string
        {
            return join($delimiter, $this->ToArray());
        }

        public static function fromString(string $subject, string $delimiter = ' '): CollectionInterface
        {
            return static::from(explode($delimiter, $subject));
        }

        public function count(): int
        {
            return count($this->items);
        }

        public function getIterator(): \Generator
        {
            yield from $this->items;
        }

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
                                $row = $row[$key] ?? ($row instanceof CollectionInterface
                                    ? $row->map(function($k, $v) use($key){ return $v[$key] ?? null; })->filter()
                                    : array_filter(array_map(function($v) use($key){ return $v[$key] ?? null; }, $row))
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

        public function insert(string $query, $value): Collection
        {
            $keys = explode('.', $query);
            $row = &$this->items;

            while(($key = array_shift($keys)) !== null && $row !== null)
            {
                $row = &$row[$key] ?? new static;
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

        public function where($expression = ''): Queryable
        {
            // TODO(Chris Kruining)
            // Implement this function

            return $this->filter(function($v) { return true; });
        }

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

        public function limit(int $length): Queryable
        {
            return $this->slice(0, $length);
        }
        public function offset(int $start): Queryable
        {
            return $this->slice($start, null);
        }

        public function union(iterable $iterable): Queryable
        {
            return static::from(array_merge($this->items, Collection::from($iterable)->toArray()));
        }

        public function distinct(string $key): Queryable
        {
            return static::from(array_unique(array_map(function($v) use($key){ return $v[$key]; }, $this->items)));
        }

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

        public function group(string $key): Queryable
        {
            $this->groupKey = $key;

            return $this;
        }

        public function sum(string $key = null)
        {
            return $this->columnAction('array_sum', $key ?? '');
        }

        public function average(string $key)
        {
            return $this->columnAction(function($arr){ return array_sum($arr) / count($arr); }, $key ?? '');
        }

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

        public function offsetExists($offset): bool
        {
            return key_exists($offset, $this->items);
        }

        public function offsetGet($offset)
        {
            return key_exists($offset, $this->items)
                ? $this->items[$offset]
                : $this->select($offset);
        }

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

        public function offsetUnset($offset)
        {
            unset($this->items[$offset]);
        }

        public function serialize(): string
        {
            return json_encode($this->ToArray());
        }

        public function unserialize($serialized): Collection
        {
            return static::From(json_decode($serialized, true));
        }

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
                return $method($this->items, ...$args);
            }

            $groups = $this->distinct($this->groupKey)->toArray();
            $results = [];

            foreach($groups as $group)
            {
                $set = $this->filter(function($v) use($group){ return $v[$this->groupKey] === $group; });
                $results[] = $method($set->select($key)->toArray());
            }

            return static::from($results);
        }

        private function sortCall(string $function, ...$arguments): CollectionInterface
        {
            $items = $this->items;

            $function($items, ...$arguments);

            return static::from($items);
        }
    }
}
