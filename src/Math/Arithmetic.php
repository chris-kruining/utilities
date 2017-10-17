<?php

namespace CPB\Utilities\Math
{
    class Arithmetic
    {
        public static function modulus(int $input, int $max) : int
        {
            return $max === 0
                ? 0
                : (($input % $max) + $max) % $max;
        }
    }
}
