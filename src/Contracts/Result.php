<?php

namespace CPB\Utilities\Contracts
{
    interface Result extends \Countable, \JsonSerializable
    {
        public function __invoke(): bool;

        public function getResult(): bool;
        public function getErrors(): array;
    }
}