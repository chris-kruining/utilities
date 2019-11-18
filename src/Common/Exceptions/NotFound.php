<?php

namespace CPB\Utilities\Common\Exceptions
{
    use Psr\Container\NotFoundExceptionInterface;

    class NotFound extends \Exception implements NotFoundExceptionInterface
    {
        public function __construct(\Throwable $previous = null)
        {
            parent::__construct('The requested key(s) can\'t be found', 0, $previous);
        }
    }
}
