<?php

namespace CPB\Utilities\Code
{
    use CPB\Utilities\Common\Collection;
    use CPB\Utilities\Common\Regex;

    class Lambda implements ParserInterface
    {
        protected $lambda;
        protected $scope;
        protected static $usePool;

        public function __invoke(...$arguments)
        {
            return $this->Parse()->__invoke(...$arguments);
        }

        public function Parse() : \Closure
        {
            try
            {
                $parts = Regex::Match('/(.*?)\s*(?:->\s*(.*?))?\s*(:.*?)?\s*=>\s*(.*)/s', $this->lambda);
                array_shift($parts);

                list($parameters, $use, $returnType, $body) = $parts;

                $parameters = Collection::From(preg_split('/,\s*/', trim($parameters, '()')))
                    ->Each(function($key, $value){
                        // NOTE(Chris Kruining)
                        // This removed unnecessary spaces
                        yield $key => join(' ', preg_split('/\s+/', $value));
                    });

                $body = preg_replace_callback_array([
                    '/\{(.*)\}/s' => function($match) use (&$prefixReturn){
                        return $match[1];
                    },
                    '/\s*(.*)\s*/' => function($match){
                        return $match[1];
                    },
                ], $body);

                if(!(strpos($body, 'yield') !== false || strpos($body, 'return') !== false))
                {
                    $body = 'return ' . rtrim($body, ';') . ';';
                }

                if(strlen($use) > 0)
                {
                    $pool = static::GetUsePool();

                    foreach(preg_split('/,\s*/', $use) as $var)
                    {
                        $var = ltrim($var, '$');

                        if(!isset($pool[$var]))
                        {
                            throw new \Exception(
                                'Use argument $' . $var . ' is unknown to the pool'
                            );
                        }

                        $$var = $pool[$var];
                    }

                    $use = 'use(' . $use . ')';
                }

                return eval('return function(' . $parameters->Join(',') . ')' . $use . $returnType . '{' . $body . '};');
            }
            catch(\Throwable $e)
            {
                throw new UnparsableException($e);
            }
        }

        public function Scope($scope) : ParserInterface
        {
            $this->scope = $scope;

            return $this;
        }

        public function Use(array $uses) : ParserInterface
        {
            foreach($uses as $key => $value)
            {
                static::GetUsePool()[$key] = $value;
            }

            return $this;
        }

        public static function From(string $lambda) : ParserInterface
        {
            $inst = new static;
            $inst->lambda = $lambda;

            return $inst;
        }

        public static function GetUsePool() : Collection
        {
            if(static::$usePool === null)
            {
                static::$usePool = new Collection;
            }

            return static::$usePool;
        }

        public static function __callStatic($name, $arguments)
        {
            if(count($arguments) === 0)
            {
                return static::GetUsePool()[$name];
            }

            static::GetUsePool()[$name] = $arguments[0];
        }

        public static function ToCallable($callable): callable
        {
            if(!is_callable($callable) && !is_string($callable))
            {
                throw new \InvalidArgumentException(
                    '$callback is not a valid parameter'
                );
            }

            return !is_callable($callable) && is_string($callable)
                ? Lambda::From($callable)
                : $callable;
        }
    }
}