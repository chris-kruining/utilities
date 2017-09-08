<?php

namespace CPB\Utilities\Contracts
{
    interface Queryable extends \Countable
    {
        const DIRECTION_ASC = 0;
        const DIRECTION_DESC = 1;

        const JOIN_INNER = 0;
        const JOIN_OUTER = 1;
        const JOIN_LEFT = 2;
        const JOIN_RIGHT = 3;

        public function select($key): Queryable;
        public function where(): Queryable;
        public function join(iterable $iterable, string $localKey, string $foreignKey, int $strategy = self::JOIN_INNER): Queryable;
        public function limit(int $length): Queryable;
        public function offset(int $start): Queryable;
        public function union(iterable $iterable): Queryable;
        public function distinct(string $key): Queryable;
        public function order(string $key, int $direction): Queryable;
        public function group(string $key): Queryable;
        public function sum(string $key = null);
        public function average(string $key);
        public function max(string $key, float $limit);
        public function min(string $key, float $limit);
        public function clamp(string $key, float $lower, float $upper);
        public function contains($value): bool;
    }
}
