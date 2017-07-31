<?php

namespace CPB\Utilities\Code
{
    interface ParserInterface
    {
        public function __invoke();

        public function Parse() : \Closure;
        public function Scope($scope) : ParserInterface;

        public static function From(string $lambda) : ParserInterface;
    }
}