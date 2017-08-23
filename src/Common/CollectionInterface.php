<?php

namespace CPB\Utilities\Common
{
    use CPB\Utilities\Contracts\Cloneable;
    use CPB\Utilities\Contracts\Queryable;

    interface CollectionInterface extends \Countable, \IteratorAggregate, \ArrayAccess, \Serializable, \JsonSerializable, Cloneable, Queryable
    {
        public function map($callback): CollectionInterface;
        public function filter($callback): CollectionInterface;
        public function each($callback): CollectionInterface;

        public function toArray(): array;
        public function toObject(): \stdClass;
        public function toString(string $delimiter = ''): string;

        public static function From(iterable $items): CollectionInterface;
    }
}
