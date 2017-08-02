<?php

namespace CPB\Utilities\Common
{
    interface CollectionInterface extends \Countable, \IteratorAggregate, \ArrayAccess, \Serializable, \JsonSerializable, Cloneable
    {
        public function toArray() : array;

        public static function From(array $items): CollectionInterface;
    }
}
