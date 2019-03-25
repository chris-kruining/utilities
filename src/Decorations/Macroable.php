<?php

namespace CPB\Utilities\Decorations
{
    use CPB\Utilities\Collections\Collection;
    use CPB\Utilities\Common\Exceptions\MethodNotAvailable;

    trait Macroable
    {
        private static
            $classes = null
        ;

        private
            $callback = null
        ;

        public function __construct(callable $callback = null)
        {
            if(self::$classes === null)
            {
                self::$classes = new Collection;
            }

            $class = self::class;
            $methods = Collection::from((new \ReflectionClass($class))->getMethods())
                ->filter(function(\ReflectionMethod $m) {
                    return \strpos($m->getDocComment() ?: '', '@macro') !== false;
                })
                ->map(function(int $k, \ReflectionMethod $v){ return $v->name; });

            self::$classes[$class] = $methods;
            $this->callback = $callback;
        }

        public function __call(string $name, array $arguments)
        {
            if(self::mayCall($name) === false)
            {
                if($this->callback === null)
                {
                    throw new MethodNotAvailable(self::class, $name);
                }
                else
                {
                    return ($this->callback)($name, $arguments);
                }
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

        private function onCall(callable $callback): void
        {
            $this->callback = $callback;
        }
    }
}
