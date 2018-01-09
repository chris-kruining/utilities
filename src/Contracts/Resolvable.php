<?php

namespace CPB\Utilities\Contracts
{
    use Psr\Container\ContainerInterface;
    
    interface Resolvable extends ContainerInterface
    {
        public function has($key, string ...$keys): bool;
        
        public function get($key, string ...$keys): Resolvable;
    }
}
