<?php

namespace CPB\Utilities\Common
{
    class Enum
    {
        private $value;
        
        public final function __construct($value = null)
        {
            $this->value = $value;
        }
    }
}
