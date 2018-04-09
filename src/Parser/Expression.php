<?php

namespace CPB\Utilities\Parser
{
    use CPB\Utilities\Collections\Collection;
    use CPB\Utilities\Common\Regex;
    use CPB\Utilities\Contracts\Resolvable;
    
    // TODO(Chris Kruining)
    // - Add a proper class that tokenize's
    //   the query string, this in now an
    //   in-class method(`split`).
    final class Expression implements ResolverInterface
    {
        private
            $query = null,
            $resolvable = null,
            $variables = null,
            $keys = null
        ;
    
        private static
            $booted = false,
            $operators,
            $longestOperator
        ;
        
        public function __construct()
        {
            $this->boot();
        }
        
        public function __invoke(Resolvable $resolvable, iterable $variables = [])
        {
            if(\strlen($this->query) === 0)
            {
                return $this->query;
            }
    
            $this->resolvable = $resolvable;
            $this->variables = Collection::from($variables);
    
            return $this->{$this->keys->includes('?') ? 'resolveTernary' : 'resolve'}($this->keys);
        }
    
        public static function init(string $query): ResolverInterface
        {
            $inst = new Expression;
            $inst->query = $query;
            $inst->keys = $inst->split($query);
            
            return $inst;
        }
    
        private function boot(): void
        {
            if(self::$booted === false)
            {
                $operators = [
                    '+', '*', '-', '/', '%' , '**',              // Math operators
                    '<', '>', '<=', '>=', '==', '!=', '=',       // Comparison operators
                    '??', '?:', '?', ':',                        // Other operators
                    '(', ')', '[', ']',                          // Nesting operators
                    '{{', '}}', '\*', '*\\', '\\', '\'',         // Custom operators
                    'in', 'where', 'limit', 'and', 'or', 'from', // Keyword operators
                ];
                
                // NOTE(Chris Kruining)
                // The operators are reversed
                // so that when searching over
                // the collection larger operators
                // are tested first, otherwise
                // larger operators which contain
                // smaller would never get matched.
                self::$operators = Collection::from($operators)->reverse();
                
                // Note(Chris Kruining)
                // Largest size is stored
                // for optimization
                self::$longestOperator = \max(\array_map('strlen', $operators));
            
                self::$booted = true;
            }
        }
    
        private function split(string $query): Collection
        {
            $out = [];
            $pos = 0;
        
            $escaped = 0;
            $group = 0;
            $shift = false;
            $string = -1;
            $length = \strlen($query);
            
            for($i = 0; $i < $length; $i++)
            {
                if(!\key_exists($pos, $out))
                {
                    $out[$pos] = '';
                }
            
                // Write preparations
                $character = $query[$i];
                
                // Match for operator
                $subQuery = strtolower(\substr($query, $i, self::$longestOperator));
                $operator = self::$operators->find(function($v) use($subQuery){ return \strpos($subQuery, $v) === 0; });
                
                if($operator !== Collection::UNDEFINED)
                {
                    $i += \strlen($operator) - 1;
                    $character = $operator;
                }
            
                if(self::$operators->includes($character) && $escaped > -1)
                {
                    switch($character)
                    {
                        case '\*':
                        case '{{':
                            $group++;
                            $escaped = $group;
                            break;
                    
                        case '*\\':
                        case '}}':
                            if($group === $escaped)
                            {
                                $escaped = 0;
                            }
                        
                            $group--;
                            break;
                    
                        case '(':
                        case '[':
                            $group++;
                            break;
                    
                        case ')':
                        case ']':
                            $group--;
                            break;
                    
                        case '\'':
                            if($string === -1)
                            {
                                $string = $group;
                            }
                            elseif($string === $group)
                            {
                                $string = -1;
                            }
                        
                            break;
                    
                        case '\\':
                            $escaped = -1;
                            break;
                    
                        default:
                            if($group === 0 && $string === -1)
                            {
                                $pos++;
                                $out[$pos] = '';
                                $shift = true;
                            }
                        
                            break;
                    }
                }
                elseif($escaped < 0)
                {
                    $escaped++;
                }
            
                // Write pre-checks
                if($out[$pos] === '' && $character === ' ' && $escaped === 0)
                {
                    continue;
                }
            
                // Write
                $out[$pos] .= $character;
            
                if($shift && $group === 0 && $escaped === 0)
                {
                    $out[$pos] = $out[$pos];
                
                    $pos++;
                    $shift = false;
                }
            }
        
            return Collection::from($out)->map(function($k, $v){ return \trim($v); });
        }
        private function resolve(Collection $keys)
        {
            $parsedKeys = [
                'keys' => [],
                'operators'  => [
                    null,
                ],
            ];
        
            $keys->map(function($key, $value) use(&$parsedKeys){
                $parsedKeys[$key % 2 === 0 ? 'keys' : 'operators'][] = $value;
            });
        
            $value = '';
        
            foreach($parsedKeys['keys'] as $i => $key)
            {
                $value = $this->parseOperator(
                    $value,
                    $parsedKeys['operators'][$i] ?? null,
                    $this->resolveKey($key)
                );
            }
        
            return $value;
        }
        private function resolveTernary(Collection $keys)
        {
            $lower = $keys->search('?');
            $upper = $keys->search(':');
        
            $result = (bool)$this->get($keys->slice(0, $lower)->toString(''));
        
            return $this->get($keys
                ->slice(...($result
                    ? [$lower + 1, $upper - $lower - 1]
                    : [$upper + 1])
                )
                ->toString('')
            );
        }
        private function resolveKey($key)
        {
            $trimmed = \trim($key);
        
            if(\strlen($trimmed) === 0)
            {
                return $key;
            }
        
            $parts = Regex::match('/^([a-zA-Z_]+[a-zA-Z0-9_]*)\((.*)\)$/', $trimmed);
            \array_shift($parts);
        
            $callable = $parts[0] ?? '';
            $parameters = $this->parseParameters($parts[1] ?? '')
                ->map(function($k, $v){
                    $res = static::init($v)($this->resolvable, $this->variables);
    
                    return $res instanceof Resolvable
                        ? $res->first()
                        : $res ?? [];
                })
                ->toArray();
        
            switch(true)
            {
                // Parse escaped key
                case $trimmed[0] === '\\':
                    return substr($trimmed, 1);
    
                // Parse arrays
                case ($match = Regex::match('/^\[(.*)\]$/', $trimmed)[1] ?? null) !== null:
                    return $this->parseParameters($match)
                        ->map(function($k, $v){ return static::init($v)($this->resolvable, $this->variables); })
                        ->toArray();
            
                // Parse 'sub-queries'
                case $trimmed[0] === '(' && $trimmed[-1] === ')':
                    return static::init(substr($trimmed, 1, -1))($this->resolvable, $this->variables);
            
                // Parse function
                case method_exists($this->resolvable, $callable):
                    return $this->resolvable->$callable(...$parameters);
    
                case \is_callable($callable):
                    return $callable(...$parameters);
            
                // Fetch the variable
                case substr($trimmed, 0, 2) === '{{' && substr($trimmed, -2, 2) === '}}':
                    if($this->variables === null || !$this->variables->has(substr($trimmed, 2, -2)))
                    {
                        return null;
                    }
                    
                    return $this->variables[substr($trimmed, 2, -2)];
            
                // Fetch the field
                case $trimmed[0] === '$':
                    return $this->resolvable->resolve(substr($trimmed, 1));
            
                // Fetch property
                case $trimmed[0] === '#':
                    return $this->resolvable->{substr($trimmed, 1)};
            
                // just return the key as is
                default:
                    return $key;
            }
        }
        private function parseOperator($left, ?string $operator, $right)
        {
            switch($operator)
            {
                // NOTE(Chris Kruining)
                // This is either a math or
                // a concatenation operator
                case '+':
                    if(!is_numeric($left) || !is_numeric($right))
                    {
                        if(is_string($left) || is_string($right))
                        {
                            return $left . $right;
                        }
                    
                        return null;
                    }
            
                // NOTE(Chris Kruining)
                // These are all math operators
                case '*':
                case '-':
                case '/':
                case '%':
                case '**':
                    if(!is_numeric($left))
                    {
                        throw new \Exception(\sprintf(
                            '`%s`\'s left hand is not numeric, `%s` given.',
                            $operator,
                            \gettype($left)
                        ));
                    }
                
                    if(!is_numeric($right))
                    {
                        throw new \Exception(\sprintf(
                            '`%s`\'s right hand is not numeric, `%s` given.',
                            $operator,
                            \gettype($right)
                        ));
                    }
    
                // NOTE(Chris Kruining)
                // Parse the assignment operator
                // to a strict equals operator
                // since the expression does not
                // work with assignment syntax,
                // this way I can also support
                // sql-esc syntax
                case '=':
                    $operator = '===';
    
                // NOTE(Chris Kruining)
                // These are all comparison operators
                case '<':
                case '>':
                case '<=':
                case '>=':
                case '==':
                case '!=':
                case '??':
                case '?:':
                    $left = \is_numeric($left)
                        ? \floatval($left)
                        : \sprintf('\'%s\'', \addslashes($left));
                
                    $right = \is_numeric($right)
                        ? \floatval($right)
                        : \sprintf('\'%s\'', \addslashes($right));
                
                    return eval(\sprintf('return %s %s %s;', $left ?? 'null', $operator, $right));
                    
                case 'in':
                    if(!\is_iterable($right))
                    {
                        throw new \InvalidArgumentException(
                            'in operator expected right hand value to be iterable'
                        );
                    }
                    
                    return Collection::from($right)->includes($left);
                    
                default:
                    return $right;
            }
        }
        private function parseParameters(string $parameters): Collection
        {
            $out = [];
            $pos = 0;
            $level = 0;
            $length = \strlen($parameters);
        
            for($i = 0; $i < $length; $i++)
            {
                if(!\key_exists($pos, $out))
                {
                    $out[$pos] = '';
                }
            
                $character = $parameters[$i];
            
                if($character === ',' && $level === 0)
                {
                    $pos++;
                }
                else
                {
                    if(\in_array($character, ['[', '(']))
                    {
                        $level++;
                    }
                    elseif(\in_array($character, [']', ')']))
                    {
                        $level--;
                    }
                
                    $out[$pos] .= $character;
                }
            }
        
            return Collection::from($out);
        }
    }
}
