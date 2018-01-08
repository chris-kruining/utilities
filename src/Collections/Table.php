<?php

namespace CPB\Utilities\Collections
{
    use CPB\Utilities\Common\CollectionInterface;
    use CPB\Utilities\Common\Regex;
    use CPB\Utilities\Contracts\Queryable;
    use CPB\Utilities\Enums\JoinStrategy;
    use CPB\Utilities\Enums\SortDirection;
    
    class Table extends Map implements Queryable
    {
        /**
         * Queries over the items
         *
         * @lazy-chainable false
         */
        public function select($query)
        {
//            \var_dump($query, \debug_backtrace(0));
            
            $resolver = function($row, $q) use(&$resolver) {
                $keys = Collection::from(Regex::split('/,\s*(?![^()]*(?:\([^()]*\))?\))/', $q, -1, PREG_SPLIT_NO_EMPTY));
            
                return $keys->each(function($k, $f) use($row, &$resolver){
                    $function = Regex::match('/(.*?)\((.*)\)/', $f);
                
                    if(count($function) > 0)
                    {
                        // TODO(Chris Kruining)
                        // Implement external parser
                    
                        if(method_exists($this, $function[1]))
                        {
                            $row = $this->{$function[1]}(...explode(',', $function[2]));
                        }
                        else
                        {
                            $row = null;
                        }
                    }
                    else
                    {
                        $keys = explode('.', $f);
                    
                        while(($key = array_shift($keys)) !== null && $row !== null)
                        {
                            if($key === '*')
                            {
                                $row = $row instanceof CollectionInterface
                                    ? $row->values()
                                    : array_values($row);
                            }
                            else
                            {
                                $map = function($k, $v) use($key){ return $v[$key] ?? null; };
                                $filter = function($v) { return $v !== null; };
                            
                                $row = $row[$key] ?? ($row instanceof CollectionInterface
                                        ? $row->map($map)->filter($filter)
                                        : array_filter(
                                            array_map($map, \array_keys($row), \array_values($row)),
                                            $filter
                                        )
                                    ) ?? null;
                            }
                        }
                    
                    }
                
                    yield $f => $row;
                })
                    ->toArray();
            };
        
            $data = Map::from($resolver($this->items, $query));
        
            return count($data) === 1
                ? $data[0]
                : $data;
        }
    
        /**
         * Inserts value into the Items based on provided query
         *
         * TODO(Chris Kruining)
         * Rethink implementation to
         * properly support overriding
         * existing keys...
         */
        public function insert(string $query, $value): Queryable
        {
            $keys = explode('.', $query);
            $row = &$this->items;
        
            while(($key = array_shift($keys)) !== null && $row !== null)
            {
                $newVal = \count($keys) > 0 || $key === '+'
                    ? new static
                    : null;
            
                if($key === '+')
                {
                    $key = null;
                }
                elseif(\is_array($row) && !\key_exists($key, $row))
                {
                    $row[$key] = $newVal;
                }
                elseif($row instanceof static && !$row->has($key))
                {
                    $row->items[$key] = $newVal;
                }
            
                if(!(count($keys) === 0 && $row instanceof static))
                {
                    $row = &$row[$key];
                }
                elseif(count($keys) === 0 && $row instanceof static)
                {
                    break;
                }
            }
        
            if($row instanceof static && $key !== null)
            {
                $row[$key] = $value;
            }
            elseif(is_iterable($row))
            {
                $row[] = $value;
            }
            else
            {
                $row = $value;
            }
        
            return $this;
        }
    
        /**
         * Filters items
         *
         * @lazy-chainable true
         * @alias filter
         */
        public function where($expression = ''): Queryable
        {
            // TODO(Chris Kruining)
            // Implement method
            return $this->filter(function($v) { return true; });
        }
    
        /**
         * Executes a mysql'esc JOIN on the Collection
         *
         * @lazy-chainable true
         */
        public function join(
            iterable $iterable,
            string $localKey,
            string $foreignKey,
            JoinStrategy $strategy = null
        ): Queryable
        {
            $iterable = static::from($iterable)->map(function($k, $v){
                return array_combine(
                    array_map(
                        function($key) {
                            return 'right' . $key;
                        },
                        array_keys($v)
                    ),
                    $v
                );
            })->toArray();
            $foreignKey = 'right' . $foreignKey;
        
            $leftIndex = array_map(function($row) use($localKey){ return $row[$localKey]; }, $this->items);
            $rightIndex = array_map(function($row) use($foreignKey){ return $row[$foreignKey]; }, $iterable);
            $matchedIndexes = array_map(function($v) use ($rightIndex){ return array_search($v, $rightIndex); }, array_intersect($leftIndex, $rightIndex));
        
            switch($strategy ?? JoinStrategy::INNER)
            {
                // both collections need to have a matching value
                case Queryable::JOIN_INNER:
                    $result = array_map(function($k, $v) use($iterable) {
                        return array_merge(
                            $this->items[$k],
                            $iterable[$v]
                        );
                    }, array_keys($matchedIndexes), $matchedIndexes);
                
                    break;
                // all rows from both collections and intersect matching rows
                case Queryable::JOIN_OUTER:
                    $result = [];
                    $usedIndexes = [];
                
                    foreach($this->items as $i => $row)
                    {
                        if(key_exists($i, $matchedIndexes))
                        {
                            $usedIndexes[] = $matchedIndexes[$i];
                        
                            $right = $iterable[$matchedIndexes[$i]];
                        }
                    
                        $result[] = array_merge(
                            $row,
                            $right ?? []
                        );
                    }
                
                    $result = array_merge($result, array_filter($iterable, function($i) use($usedIndexes){ return !in_array($i, $usedIndexes); }, ARRAY_FILTER_USE_KEY));
                
                    break;
                // all rows from left collection and intersect matching rows
                case Queryable::JOIN_LEFT:
                    $result = [];
                
                    foreach($this->items as $i => $row)
                    {
                        $result[] = array_merge(
                            $row,
                            $iterable[$matchedIndexes[$i] ?? -1] ?? []
                        );
                    }
                
                    break;
                // all rows from right collection and intersect matching rows
                case Queryable::JOIN_RIGHT:
                    $result = [];
                
                    $matchedIndexes = array_flip($matchedIndexes);
                
                    foreach($iterable as $i => $row)
                    {
                        $result[] = array_merge(
                            $this->items[$matchedIndexes[$i] ?? -1] ?? [],
                            $row
                        );
                    }
                
                    break;
            }
        
            return static::from($result);
        }
    
        /**
         * Return sub-selection of items
         *
         * @lazy-chainable true
         * @alias slice
         */
        public function limit(int $length): Queryable
        {
            return $this->slice(0, $length);
        }
    
        /**
         * Return sub-selection of items
         *
         * @lazy-chainable true
         * @alias slice
         */
        public function offset(int $start): Queryable
        {
            return $this->slice($start, null);
        }
    
        /**
         * Executes a mysql'esc UNION on Collction
         *
         * @lazy-chainable true
         * @alias merge
         */
        public function union(iterable $iterable): Queryable
        {
            return static::from(array_merge($this->items, Collection::from($iterable)->toArray()));
        }
    
        /**
         * Fetches all unique values of provided key
         *
         * @lazy-chainable true
         */
        public function distinct(string $key): Queryable
        {
            return static::from(array_unique(array_map(function($v) use($key){ return $v[$key]; }, $this->items)));
        }
    
        /**
         * Sort items by provided key and direction
         *
         * @lazy-chainable true
         */
        public function order(string $key, SortDirection $direction = null): Queryable
        {
            return $this->uASort(function($a, $b) use($key, $direction){
                if($direction ?? SortDirection::ASC === SortDirection::DESC)
                {
                    [$b, $a] = [$a, $b];
                }
            
                return $a[$key] ?? null <=> $b[$key] ?? null;
            });
        }
    
        /**
         * Prepares the groups of later queries
         *
         * This method sets the key by which the results
         * of: sum, average, max, min and clamp, will
         * group by
         *
         * @lazy-chainable true
         */
        public function group(string $key): Queryable
        {
            $this->groupKey = $key;
        
            return $this;
        }
        
        public function offsetGet($offset)
        {
            return \is_string($offset) && !\key_exists($offset, $this->items)
                ? $this->select($offset)
                : parent::offsetGet($offset);
        }
        public function offsetSet($offset, $value)
        {
            switch(\gettype($offset))
            {
                case 'string':
                case 'integer':
                    $this->insert($offset, $value);
                    break;
            
                case 'NULL':
                    $this->items[] = $value;
                    break;
            
                default:
                    throw new \InvalidArgumentException;
            }
        }
    }
}
