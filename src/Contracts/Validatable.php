<?php

namespace CPB\Utilities\Contracts
{
    interface Validatable
    {
        public function validate(string $rule = null, string ...$rules): Result;
    }
}