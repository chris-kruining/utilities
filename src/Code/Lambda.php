<?php

namespace CPB\Utilities\Code
{
    use CPB\Utilities\Common\Collection;
    use CPB\Utilities\Common\Regex;

    class Lambda implements ParserInterface
    {
        protected $lambda;
        protected $scope;

        public function __invoke(...$arguments)
        {
            return $this->Parse()->__invoke(...$arguments);
        }

        public function Parse() : \Closure
        {
            try
            {
                $parts = Regex::Match('/(.*?)\s*(->.*?)?\s*(:.*?)?\s*=>\s*(.*)/s', $this->lambda);
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
                    $use = str_replace('->', 'use(', $use) . ')';
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

        public static function From(string $lambda) : ParserInterface
        {
            $inst = new static;
            $inst->lambda = $lambda;

            return $inst;
        }
    }
}