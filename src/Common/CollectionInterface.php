<?php

namespace CPB\Utilities\Common
{
    use CPB\Utilities\Contracts\Cloneable;
    use CPB\Utilities\Contracts\Queryable;
    use CPB\Utilities\Contracts\Resolvable;
    use Psr\Container\ContainerInterface;

    interface CollectionInterface extends
        Resolvable,
        \Countable,
        \IteratorAggregate,
        \ArrayAccess,
        \Serializable,
        \JsonSerializable,
        Cloneable
    {
        public function merge(iterable ...$sets): CollectionInterface;

        public function map(callable $callback): CollectionInterface;

        public function filter(callable $callback = null): CollectionInterface;

        public function reject(callable $callback): CollectionInterface;

        public function each(callable $callback): CollectionInterface;

        public function split(callable $callback, int $option = 0): array;

        public function slice(int $start, int $length = null) : CollectionInterface;

        public function splice(int $start, int $length = null, $replacement = []) : CollectionInterface;

        public function keys(): CollectionInterface;

        public function values(): CollectionInterface;
    
        public function flip(): CollectionInterface;

        public function unique(): CollectionInterface;

        public function reverse(): CollectionInterface;
        
        public function reduce(callable $callback, $input = null);

        public function find(callable $callback);

        public function every(callable $callback): bool;

        public function some(callable $callback): bool;

        public function includes($value): bool;

        public function diff(iterable ...$sets): CollectionInterface;

        public function diffAssoc(iterable ...$sets): CollectionInterface;
        
        public function diffKey(iterable ...$sets): CollectionInterface;
        
        public function diffUAssoc(callable $callback, iterable ...$sets): CollectionInterface;
        
        public function diffUKey(callable $callback, iterable ...$sets): CollectionInterface;

        public function intersect(iterable ...$sets): CollectionInterface;

        public function intersectAssoc(iterable ...$sets): CollectionInterface;
        
        public function intersectKey(iterable ...$sets): CollectionInterface;
        
        public function intersectUAssoc(callable $callback, iterable ...$sets): CollectionInterface;
        
        public function intersectUKey(callable $callback, iterable ...$sets): CollectionInterface;

        public function sort(int $flags = SORT_REGULAR): CollectionInterface;

        public function rSort(int $flags = SORT_REGULAR): CollectionInterface;

        public function aSort(int $flags = SORT_REGULAR): CollectionInterface;

        public function aRSort(int $flags = SORT_REGULAR): CollectionInterface;

        public function kSort(int $flags = SORT_REGULAR): CollectionInterface;

        public function kRSort(int $flags = SORT_REGULAR): CollectionInterface;

        public function uSort(callable $callback): CollectionInterface;

        public function uASort(callable $callback): CollectionInterface;

        public function uKSort(callable $callback): CollectionInterface;

        public function topologicalSort(string $edgeKey): CollectionInterface;

        public function byIndex(int $i);

        public function first();

        public function last();

        public function chunk(int $size, bool $preserveKeys = false): CollectionInterface;

        public function combine(iterable $values): CollectionInterface;

        public function isAssociative(): bool;

        public function toArray(): array;

        public function toObject(): \stdClass;

        public function toString(string $delimiter = ''): string;

        public static function From(iterable $items): CollectionInterface;
    }
}
