<?php

namespace CPB\Utilities\Enums
{
    use CPB\Utilities\Common\Enum;
    
    final class JoinStrategy extends Enum
    {
        public const
            INNER = 0,
            OUTER = 1,
            LEFT = 2,
            RIGHT = 3
        ;
    }
}
