<?php

namespace CPB\Utilities\Common
{
    use CPB\Utilities\Code\Lambda;
    use CPB\Utilities\Code\UnparsableException;

    class Collection implements \Countable, \IteratorAggregate, \ArrayAccess, \Serializable, \JsonSerializable
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
                try
                {
                    return static::GetCallable($parameter);
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

        public function __debugInfo() : array
        {
            return $this->items;
        }

        public function Map($callback): Collection
        {
            $callback = static::GetCallable($callback);

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
        public function Each($callback): Collection
        {
            $callback = static::GetCallable($callback);

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

        // courtesy of https://stackoverflow.com/a/6092999
        public function PowerSet(int $minLength = 1) : Collection
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

        public function IsAssociative() : bool
        {
            return $this->Keys()->Filter('is_string')->Count() > 0;
        }

        public static function From(array $items): Collection
        {
            $inst = new static();
            $inst->items = $items;

            return $inst;
        }

        protected static function GetCallable($callable) : callable
        {
            if(!is_callable($callable) && !is_string($callable))
            {
                throw new \InvalidArgumentException(
                    '$callback is not a valid parameter'
                );
            }

            return !is_callable($callable) && is_string($callable)
                ? Lambda::From($callable)
                : $callable;
        }

        public function ToArray()
        {
            return iterator_to_array($this);
        }

        public function Join(string $delimitor = '') : string
        {
            return join($delimitor, $this->ToArray());
        }

        public function count(): int
        {
            return count($this->items);
        }

        public function getIterator()
        {
            yield from $this->items;
        }

        public function offsetExists($offset)
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

        public function serialize()
        {
            return json_encode($this->ToArray());
        }

        public function unserialize($serialized)
        {
            return static::From(json_decode($serialized, true));
        }

        function jsonSerialize()
        {
            return $this->serialize();
        }
    }
}
