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

        public static function MatchAll(string $pattern, string $subject) : array
        {
            preg_match_all($pattern, $subject, $matches);

            return $matches;
        }

        public static function Encapsulate(string $input, string $char, string $filter = null, string $lookFor = 'A-Za-z0-9_', int $limit = -1) : string
        {
            $filter = $filter ?? $char;

            return preg_replace(
                '/(?![^' . $filter . ']*[' . $filter . '](?:[^' . $filter . ']*[' . $filter . '][^' . $filter . ']*[' . $filter . '])*[^' . $filter . ']*$)([' . $lookFor . ']+)/',
                $char . '$1' . $char,
                $input,
                $limit
            );
        }

        public static function AfterLastOccurrence(string $subject, string $character = '/') : string
        {
            return static::MatchAll('/.*\\' . $character . '(.*)/', $subject)[1][0];
        }
    }
}