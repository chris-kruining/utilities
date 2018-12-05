<?php

namespace CPB\Utilities\Common\Exceptions
{
    class NotImplemented extends \Exception
    {
        public function __construct(\Throwable $previous = null)
        {
            parent::__construct(
                'The code being called does not offer an implementation',
                0,
                $previous
            );
        }
    }
}