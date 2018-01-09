<?php

namespace CPB\Utilities\Parser
{
    use CPB\Utilities\Contracts\Resolvable;
    
    interface ResolverInterface
    {
        public function __invoke();
        
        public static function init(string $query, Resolvable $resolvable): ResolverInterface;
    }
}
