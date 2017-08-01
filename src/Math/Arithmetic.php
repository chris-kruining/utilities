<?php

namespace CPB\Utilities\Math
{
    class Arithmetic
    {
        public static function Modulus(int $input, int $max) : int
        {
            return (($input % $max) + $max) % $max;
        }
    }
}