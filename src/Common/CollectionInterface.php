<?php

namespace CPB\Utilities\Common
{
    use CPB\Utilities\Contracts\Cloneable;
    use CPB\Utilities\Contracts\Queryable;

    interface CollectionInterface extends
        \Countable,
        \IteratorAggregate,
        \ArrayAccess,
        \Serializable,
        \JsonSerializable,
        Cloneable,
        Queryable
    {
        public function map(callable $callback): CollectionInterface;

        public function filter(callable $callback): CollectionInterface;

        public function each(callable $callback): CollectionInterface;

        public function append(iterable $data): CollectionInterface;

        public function split(callable $callback, int $option = 0, bool $assoc = false): array;

        public function slice(int $start, int $length = null) : CollectionInterface;

        public function splice(int $start, int $length = null, $replacement = []) : CollectionInterface;

        public function keys(): CollectionInterface;

        public function values(): CollectionInterface;

        public function unique(): CollectionInterface;

        public function find(callable $callback);

        public function every(callable $callback): bool;

        public function some(callable $callback): bool;

        public function contains($value): bool;

        public function has($key): bool;

        public function diff(array ...$arrays): CollectionInterface;

        public function diffAssoc(array ...$arrays): CollectionInterface;

        public function byIndex(int $i);

        public function first();

        public function last();

        public function isAssociative(): bool;

        public function toArray(): array;

        public function toObject(): \stdClass;

        public function toString(string $delimiter = ''): string;

        public static function From(iterable $items): CollectionInterface;
    }
}
