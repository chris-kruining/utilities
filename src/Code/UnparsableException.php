<?php

namespace CPB\Utilities\Code
{
    use Throwable;

    class UnparsableException extends \Exception
    {
        public function __construct(\Throwable $prev)
        {
            parent::__construct(
                'The argument given could not be parsed to a closure',
                $prev->getCode(),
                $prev
            );
        }
    }
}