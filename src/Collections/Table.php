<?php

namespace CPB\Utilities\Collections
{
    use Core\Utility\Exception\NotImplemented;
    use CPB\Utilities\Common\CollectionInterface;
    use CPB\Utilities\Common\NotFoundException;
    use CPB\Utilities\Common\Regex;
    use CPB\Utilities\Contracts\Queryable;
    use CPB\Utilities\Contracts\Resolvable;
    use CPB\Utilities\Enums\JoinStrategy;
    use CPB\Utilities\Enums\SortDirection;
    use CPB\Utilities\Parser\Expression;
    
    class Table extends Map implements Queryable
    {
        protected
            $type
        ;
        
        public function __construct(string $type = null)
        {
            $this->type = $type;
        }
        
        public static function from(iterable $items, string $type = null): CollectionInterface
        {
            $inst = parent::from($items);
            $inst->type = $type;
            
            return $inst;
        }
    
        public function has($key, string ...$keys): bool
        {
            \array_unshift($keys, $key);
            
            return $this->some(function($i, $row) use($keys){
                if($row instanceof Resolvable)
                {
                    return $row->has(...$keys);
                }
                elseif($row instanceof \Traversable)
                {
                    $exists = true;
    
                    foreach($keys as $key)
                    {
                        $exists &= isset($row[$key]);
                    }
                    
                    return (bool)$exists;
                }
                elseif(\is_object($row))
                {
                    $exists = true;
                    
                    foreach($keys as $key)
                    {
                        $exists &= \property_exists($row, $key);
                    }
                    
                    return (bool)$exists;
                }
                elseif(\is_array($row))
                {
                    return count(array_diff($keys, array_keys($row))) === 0;
                }
                
                return false;
            });
        }
        
        public function get($key, string ...$keys): Resolvable
        {
            \array_unshift($keys, $key);
            
            if(!$this->has(...$keys))
            {
                throw new NotFoundException;
            }
            
            return $this->map(function($i, $row) use($keys){
                if($row instanceof Resolvable)
                {
                    return $row->get(...$keys);
                }
                elseif(\is_object($row))
                {
                    $res = [];
            
                    foreach($keys as $key)
                    {
                        $res[$key] = $row->$key;
                    }
    
                    return $res;
                }
                elseif(\is_array($row))
                {
                    $res = [];
    
                    foreach($keys as $key)
                    {
                        \var_dump($row);
                        
                        $res[$key] = $row[$key];
                    }
    
                    return $res;
                }
            });
        }
    
        /**
         * Queries over the items
         */
        public function select(string $query)
        {
            $result = Expression::init($query)($this);
            
            if(!$result instanceof CollectionInterface)
            {
                $result = static::from([ [ $query => $result] ]);
            }
            
            return $result;
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
            if($this->type !== null && \is_object($value)
                ? !$value instanceof $this->type
                : \gettype($value) !== $this->type)
            {
                throw new \InvalidArgumentException(\sprintf(
                    'expected value of type %s, got %s',
                    $this->type,
                    \is_object($value)
                        ? \get_class($value)
                        : \gettype($value)
                ));
            }
            
            \var_dump($query, $value);
            die;
            
            throw new NotImplemented;
        
            return $this;
        }
    
        /**
         * Filters items
         *
         * @lazy-chainable true
         * @alias filter
         */
        public function where(string $query, iterable $variables = []): Queryable
        {
            $query = Expression::init(Regex::replace('/:([A-Za-z_][A-Za-z0-9_]*)/', $query, '{{$1}}'));
            
            return $this->filter(function($row) use($query, $variables){
                if($row instanceof Resolvable)
                {
                    return $query($row, $variables);
                }
                elseif(\is_iterable($row))
                {
                    return $query(Collection::from($row), $variables);
                }
                
                return false;
            });
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
            throw new NotImplemented;
    
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
        
        public function in(...$args)
        {
            \var_dump($args);
            die;
        }
    
        /**
         * Return sub-selection of items
         *
         * @alias slice
         */
        public function limit(int $length): Queryable
        {
            return $this->slice(0, $length);
        }
    
        /**
         * Return sub-selection of items
         *
         * @alias slice
         */
        public function offset(int $start): Queryable
        {
            return $this->slice($start, null);
        }
    
        /**
         * Executes a mysql'esc UNION on Collction
         *
         * @alias merge
         */
        public function union(iterable $iterable): Queryable
        {
            return static::from(array_merge($this->items, Collection::from($iterable)->toArray()));
        }
    
        /**
         * Fetches all unique values of provided key
         */
        public function distinct(string $key): Queryable
        {
            return static::from(array_unique(array_map(function($v) use($key){ return $v[$key]; }, $this->items)));
        }
    
        /**
         * Sort items by provided key and direction
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
