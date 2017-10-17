<?php

namespace CPB\Utilities\Enums
{
    class JoinStrategy
    {
        public const
            __default = self::INNER,
            INNER = 0,
            OUTER = 1,
            LEFT = 2,
            RIGHT = 3
        ;
    }
}