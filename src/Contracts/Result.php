<?php

namespace CPB\Utilities\Contracts
{
    interface Result extends \Countable, \JsonSerializable
    {
        public function getResult(): bool;
        public function getErrors(): array;
    }
}