<?php

namespace CPB\Utilities\Code
{
    use CPB\Utilities\Common\Collection;

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
                @list($parameters, $returnType, $body) = preg_split('/\s*(:|=>)\s*/', $this->lambda);
                if($body === null)
                {
                    $body = $returnType;
                    $returnType = null;
                }
                $parameters = Collection::From(preg_split('/,\s*/', trim($parameters, '()')))
                    ->Each(function($key, $value){
                        // NOTE(Chris Kruining)
                        // This removed unnecessary spaces
                        yield $key => join(' ', preg_split('/\s+/', $value));
                    });
                $prefixReturn = true;
                $body = preg_replace_callback_array([
                    '/\{(.*)\}/s' => function($match) use (&$prefixReturn){
                        $prefixReturn = false;

                        return $match[1];
                    },
                    '/\s*(.*)\s*/' => function($match){
                        return $match[1];
                    },
                ], $body);
                if($prefixReturn)
                {
                    $body = 'return ' . $body . ';';
                }
                $returnType = $returnType === null
                    ? ''
                    : (':' . $returnType);

                return eval('return function(' . $parameters->Join(',') . ')' . $returnType . '{' . $body . '};');
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