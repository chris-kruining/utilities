<?php

namespace CPB\Utilities\Decorations
{
    use CPB\Utilities\Collections\Collection;
    
    trait Marcoable
    {
        private static
            $classes = null
        ;
        
        public function __construct()
        {
            if(self::$classes === null)
            {
                self::$classes = new Collection;
            }
            
            $class = self::class;
            $methods = Collection::from((new \ReflectionClass($class))->getMethods())
                ->filter(function($m) { return \strpos($m->getDocComment() ?: '', '@macro') !== false; })
                ->map(function($k, $v){ return $v->name; });
    
            self::$classes[$class] = $methods;
        }
        
        public function __call(string $name, array $arguments)
        {
            if(!self::mayCall($name))
            {
                throw new MethodNotAvailable(self::class, $name);
            }
            
            return $this->$name(...$arguments);
        }
        
        public static function __callStatic(string $name, array $arguments)
        {
            // NOTE(Chris Kruining)
            // Creating a new instance without
            // conditions seems counter intuitive
            // but makes sure the called class
            // get a chance to register itself
            return (new static())->__call($name, $arguments);
        }
        
        private static function mayCall(string $method): bool
        {
            return self::$classes->has(self::class) && self::$classes[self::class]->includes($method);
        }
        
    }
}
