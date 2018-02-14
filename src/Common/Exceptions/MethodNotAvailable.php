<?php

namespace Core\Utility\Exception
{
    class MethodNotAvailable extends \BadMethodCallException
    {
        public function __construct(string $class, string $method)
        {
            $trace = $this->getTrace();
            
            parent::__construct(\sprintf(
                '\'%s::%s\' is neither a macro\'ed method nor accessible or does not exist',
                $class,
                $method
            ));
            
            $this->file = $trace[0]['file'];
            $this->line = $trace[0]['line'];
        }
    }
}
