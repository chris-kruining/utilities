<?php

namespace CPB\Utilities\Collections\Exception
{
    use CPB\Utilities\Collections\Collection;

    class KeyMissing extends \Exception
    {
        public function __construct($missing, iterable $keys)
        {
            if(is_string($missing) === false && is_array($missing) === false)
            {
                throw new \Exception('expected $missing to be either an `array` or `string`');
            }

            if(is_string($missing))
            {
                $missing = [ $missing ];
            }

            parent::__construct(\sprintf(
                'Key(\'s) missing from iterable, expected [ %s ] in [ %s ]',
                \join(', ', \array_map(fn($key) => \sprintf('`%s`', $key), $missing)),
                \join(', ', \array_map(fn($key) => \sprintf('`%s`', $key), Collection::sanitize($keys)))
            ));
        }
    }
}