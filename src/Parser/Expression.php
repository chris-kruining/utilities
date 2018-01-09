<?php

namespace CPB\Utilities\Parser
{
    use CPB\Utilities\Collections\Collection;
    use CPB\Utilities\Common\Regex;
    use CPB\Utilities\Contracts\Resolvable;
    
    class Expression implements ResolverInterface
    {
        private
            $query = null,
            $resolvable = null
        ;
    
        private static
            $booted = false,
            $operators
        ;
        
        public function __construct()
        {
            $this->boot();
        }
        
        public function __invoke()
        {
            if(\strlen($this->query) === 0)
            {
                return $this->query;
            }
    
            $keys = $this->split($this->query);
            
            return $this->{$keys->includes('?') ? 'resolveTernary' : 'resolve'}($keys);
        }
    
        public static function init(string $query, Resolvable $resolvable): ResolverInterface
        {
            $inst = new self;
            $inst->query = $query;
            $inst->resolvable = $resolvable;
            
            return $inst;
        }
    
        private function boot(): void
        {
            if(self::$booted === false)
            {
                self::$operators = Collection::from([
                    '+', '*', '-', '/', '%' , '**',            // Math operators
                    '<', '>', '<=', '>=', '==', '!=',          // Comparison operators
                    '??', '?:', '?', ':',                      // Other operators
                    '(', ')', '{{', '}}', '\\', '[', ']', '\'' // Custom operators
                ]);
            
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
        
            for($i = 0; $i < \strlen($query); $i++)
            {
                if(!\key_exists($pos, $out))
                {
                    $out[$pos] = '';
                }
            
                // Write preparations
                $character = $query[$i];
            
                if(self::$operators->includes($character . ($query[$i + 1] ?? '')))
                {
                    $i++;
                    $character .= $query[$i] ?? '';
                }
            
                if(self::$operators->includes($character) && $escaped > -1)
                {
                    switch($character)
                    {
                        case '{{':
                            $group++;
                            $escaped = $group;
                            break;
                    
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
        
            // NOTE(Chris Kruining)
            // When all is parsed and done,
            // remove all escape constructs
        
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
                ->map(function($k, $v){ return static::init($v, $this->resolvable)(); })
                ->toArray();
        
            switch(true)
            {
                // Parse escaped key
                case $trimmed[0] === '\\':
                    return substr($trimmed, 1);
    
                // Parse arrays
                case ($match = Regex::match('/^\[(.*)\]$/', $trimmed)[1] ?? null) !== null:
                    return $this->parseParameters($match)
                        ->map(function($k, $v){ return static::init($v, $this->resolvable)(); })
                        ->toArray();
            
                // Parse 'sub-queries'
                case $trimmed[0] === '(' && $trimmed[-1] === ')':
                    return static::init(substr($trimmed, 1, -1), $this->resolvable)();
    
                case \is_callable($callable):
                    return $callable(...$parameters);
            
                // Parse function
                case method_exists($this->resolvable, $callable):
                    return $this->resolvable->$callable(...$parameters);
            
                // Fetch the field
                case substr($trimmed, 0, 1) === '$' && $this->resolvable->has(substr($trimmed, 1)):
                    return $this->resolvable->get(substr($trimmed, 1));
            
                // Fetch property
                case substr($trimmed, 0, 1) === '#':
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
            
                default:
                    return $right;
            }
        }
        private function parseParameters(string $parameters): Collection
        {
            $out = [];
            $pos = 0;
            $level = 0;
        
            for($i = 0; $i < \strlen($parameters); $i++)
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
