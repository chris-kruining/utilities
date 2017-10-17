<?php

namespace CPB\Utilities\Enums
{
    class SortDirection extends \SplEnum
    {
        public const
            __default = self::ASC,
            ASC = 0,
            DESC = 1
        ;
    }
}