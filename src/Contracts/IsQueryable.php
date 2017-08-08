<?php

namespace CPB\Utilities\Contracts
{
    use CPB\Utilities\Code\Lambda;
    use CPB\Utilities\Contracts\Queryable;

    trait IsQueryable
    {
        private $_isQueryableArray;

        private function passArray(array &$array)
        {
            $this->_isQueryableArray = $array;
        }

        public function where($expression = '') : Queryable
        {
            return $this->Filter($expression);
        }

        public function join(iterable $iterable, int $strategy = Queryable::JOIN_INNER): Queryable
        {
            // TODO: Implement join() method.
        }

        public function limit(int $length) : Queryable
        {
            // TODO: Implement limit() method.
        }

        public function offset(int $start): Queryable
        {
            // TODO: Implement offset() method.
        }

        public function union(): Queryable
        {
            // TODO: Implement union() method.
        }

        public function distinct(string $key): Queryable
        {
            // TODO: Implement distinct() method.
        }

        public function order(string $key, int $direction): Queryable
        {
            // TODO: Implement order() method.
        }

        public function group(string $key): Queryable
        {
            // TODO: Implement group() method.
        }

        public function sum(string $key): float
        {

        }

        public function average(string $key): float
        {
            // TODO: Implement average() method.
        }

        public function max(float $limit): float
        {
            // TODO: Implement max() method.
        }

        public function min(float $limit): float
        {
            // TODO: Implement min() method.
        }

        public function clamp(float $lower, float $upper): float
        {
            // TODO: Implement clamp() method.
        }

        public function contains($value): bool
        {
            // TODO: Implement contains() method.
        }
    }
}
