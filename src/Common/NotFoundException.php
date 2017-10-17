<?php

namespace CPB\Utilities\Common
{
    use Psr\Container\NotFoundExceptionInterface;

    class NotFoundException extends \Exception implements NotFoundExceptionInterface
    {
        public function __construct(\Throwable $previous = null)
        {
            parent::__construct('The requested key(s) can\'t be found', 0, $previous);
        }
    }
}