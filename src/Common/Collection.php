<?php

namespace CPB\Utilities\Common
{
    class Collection implements \Countable, \IteratorAggregate, \ArrayAccess
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
                return $function($this->items, ...$parameters);
            }
        }

        public function Map(callable $callback): Collection
        {
            return static::From(array_map($callback, $this->items));
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
    }
}
