<?php

namespace CPB\Utilities\Common
{
    use CPB\Utilities\Code\Lambda;
    use CPB\Utilities\Contracts\IsQueryable;
    use CPB\Utilities\Math\Arithmetic;

    class Collection implements CollectionInterface
    {
        use IsQueryable;

        protected $items;

        public function __construct()
        {
            $this->items = [];

            $this->passArray($this->items);
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
                    return $parameter->ToArray();
                }

                try
                {
                    return Lambda::ToCallable($parameter);
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
                    ? static::From($result)
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
            return Collection::From($this->items);
        }

        public function __toString(): string
        {
            return $this->toString();
        }

        public function __debugInfo(): array
        {
            return $this->items;
        }

        public function Map($callback): CollectionInterface
        {
            $callback = Lambda::ToCallable($callback);

            return static::From(
                array_map(
                    $callback,
                    array_keys($this->items),
                    array_values($this->items)
                )
            );
        }

        // NOTE(Chris Kruining)
        // This function is meant to
        // take an action on each item
        // in the collection allowing
        // to change key and value,
        // whereas Map only allows for
        // changes in the value
        public function Each($callback): CollectionInterface
        {
            $callback = Lambda::ToCallable($callback);

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

                case Lambda::isCallable($key):
                    return $this->each($key);

                case is_string($key):
                    $keys = explode('.', $key);
                    $results = $this;

                    while(($key = array_shift($keys)) !== null)
                    {
                        $results = $results->each('$k, $v => yield $k => $v[\'' . $key . '\']');
                    }

                    return $results;

                default:
                    throw new \Exception(
                        'Can\'t parse the given key'
                    );
            }
        }

        // NOTE(Chris Kruining)
        // courtesy of https://stackoverflow.com/a/6092999
        public function PowerSet(int $minLength = 1): Collection
        {
            $count = $this->count();
            $members = pow(2, $count);
            $values = $this->Values();
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

            return static::From($return);
        }

        public function IsAssociative(): bool
        {
            return $this->Keys()->Filter('is_string')->Count() > 0;
        }

        public function Index(int $i)
        {
            $values = $this->Values();

            return $values[Arithmetic::Modulus($i, count($values))] ?? null;
        }

        public function First()
        {
            return $this->Index(0);
        }

        public function Last()
        {
            return $this->Index(-1);
        }

        public static function From(iterable $items): CollectionInterface
        {
            $inst = new static();
            $inst->items = $items instanceof \Traversable
                ? iterator_to_array($items, true)
                : $items;

            return $inst;
        }

        public function ToArray() : array
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
