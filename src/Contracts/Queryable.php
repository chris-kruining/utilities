<?php

namespace CPB\Utilities\Contracts
{
    use CPB\Utilities\Common\CollectionInterface;
    use CPB\Utilities\Enums\JoinStrategy;
    use CPB\Utilities\Enums\SortDirection;

    interface Queryable extends \Countable, CollectionInterface
    {
        public function select(string $key);

        public function where(): Queryable;

        public function limit(int $length): Queryable;

        public function offset(int $start): Queryable;

        public function join(
            iterable $iterable,
            string $localKey,
            string $foreignKey,
            JoinStrategy $strategy = null
        ): Queryable;

        public function union(iterable $iterable): Queryable;

        public function distinct(string $key): Queryable;

        public function order(string $key, SortDirection $direction = null): Queryable;

        public function group(string $key): Queryable;

        public function sum(string $key = null);

        public function average(string $key);

        public function max(string $key, float $limit = null);

        public function min(string $key, float $limit = null);

        public function clamp(string $key, float $lower, float $upper);
    }
}
