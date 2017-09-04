<?php

namespace CPB\Utilities\Common
{
    use CPB\Utilities\Code\Lambda;
    use CPB\Utilities\Contracts\Queryable;
    use CPB\Utilities\Math\Arithmetic;

    class Collection implements CollectionInterface
    {
        protected $items;

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

                try
                {
                    return Lambda::toCallable($parameter);
                }
                catch(\InvalidArgumentException $e)
                {
                    return $parameter;
                }
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

        public function filter(callable $callback, int $option = 0): CollectionInterface
        {
            return static::from(array_filter($this->items, $callback, $option));
        }

        public function slice(int $start, int $length = null) : CollectionInterface
        {
            return static::from(array_slice($this->items, $start, $length, true));
        }

        public function diff(array ...$arrays): CollectionInterface
        {
            return static::from(array_diff($this->items, ...$arrays));
        }

        public function diffAssoc(array ...$arrays): CollectionInterface
        {
            return static::from(array_diff_assoc($this->items, ...$arrays));
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

        public function select($key): CollectionInterface
        {
            switch(true)
            {
                case is_numeric($key):
                    return $this[$key];

                case is_string($key):
                    $keys = explode('.', $key);
                    $results = $this;

                    while(($key = array_shift($keys)) !== null)
                    {
                        $results = $results->each(function($k, $v) use($key){ yield $k => $v[$key]; });
                    }

                    return $results;

                default:
                    throw new \Exception(
                        'Can\'t parse the given key'
                    );
            }
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
            return $this->Keys()->Filter('is_string')->Count() > 0;
        }

        public function index(int $i)
        {
            $values = $this->values();

            return $values[Arithmetic::Modulus($i, count($values))] ?? null;
        }

        public function first()
        {
            return $this->index(0);
        }

        public function last()
        {
            return $this->index(-1);
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

        public function count(): int
        {
            return count($this->items);
        }

        public function getIterator(): \Generator
        {
            yield from $this->items;
        }

        public function where($expression = ''): Queryable
        {
            return $this->filter($expression);
        }

        public function join(iterable $iterable, string $localKey, string $foreignKey, int $strategy = Queryable::JOIN_INNER): Queryable
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

            switch($strategy)
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
            // TODO: Implement distinct() method.
        }

        public function order(string $key, int $direction): Queryable
        {
            // TODO: Implement order() method.
        }
        public function group(string $key): Queryable
        {
            // TODO: Implement group() method.
        }

        public function sum(string $key): float
        {

        }
        public function average(string $key): float
        {
            // TODO: Implement average() method.
        }
        public function max(float $limit): float
        {
            // TODO: Implement max() method.
        }
        public function min(float $limit): float
        {
            // TODO: Implement min() method.
        }
        public function clamp(float $lower, float $upper): float
        {
            // TODO: Implement clamp() method.
        }

        public function contains($value): bool
        {
            // TODO: Implement contains() method.
        }

        public function offsetExists($offset): bool
        {
            return key_exists($offset, $this->items);
        }
        public function offsetGet($offset)
        {
            return $this->items[$offset];
        }
        public function offsetSet($offset, $value)
        {
            $this->items[$offset] = $value;
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
    }
}
