<?php

namespace CPB\Utilities\Common
{
    class Regex
    {
        public static function Match(string $pattern, string $subject) : array
        {
            preg_match($pattern, $subject, $matches);

            return $matches;
        }
    }
}