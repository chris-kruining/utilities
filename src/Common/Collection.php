<?php

namespace CPB\Utilities\Common
{
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

        public function Map(callable $callback): Collection
        {
            return static::From(
				array_map($callback, $this->Keys(), $this->Values())
			);
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

        public static function From(array $items): Collection
        {
            $inst = new static();
            $inst->items = $items;

            return $inst;
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
