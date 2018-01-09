<?php

namespace CPB\Utilities\Common
{
    class Enum implements \JsonSerializable
    {
        private
            $value
        ;
        
        private static
            $map = []
        ;
        
        public final function __construct($value = null)
        {
            $this->value = $value;
        }
        
        public final function __toString(): string
        {
            return (string)$this->value;
        }
    
        public final function getValue()
        {
            return $this->value;
        }
        
        public final function jsonSerialize()
        {
            $constants = self::$map[static::class];
            
            return \sprintf('%s::%s', static::class, \array_flip($constants)[$this->value]);
        }
    
        public final static function from($key)
        {
            if(!\key_exists(static::class, self::$map))
            {
                self::$map[static::class] = (new \ReflectionClass(static::class))->getConstants();
            }
            
            $constants = self::$map[static::class];
            
            
            if(!\key_exists($key, $constants) && \array_search($key, $constants) === false)
            {
                throw new \InvalidArgumentException(\sprintf(
                    '%s is not does not exist in %s',
                    $key,
                    static::class
                ));
            }
            
            $value = \key_exists($key, $constants)
                ? $constants[$key]
                : $key;
            
            return new static($value);
        }
    }
}
