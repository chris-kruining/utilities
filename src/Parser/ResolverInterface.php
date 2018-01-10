<?php

namespace CPB\Utilities\Parser
{
    use CPB\Utilities\Contracts\Resolvable;
    
    interface ResolverInterface
    {
        public function __invoke(Resolvable $resolvable, iterable $variables = []);
        
        public static function init(string $query): ResolverInterface;
    }
}
