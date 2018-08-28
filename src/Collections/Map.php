<?php

namespace CPB\Utilities\Collections
{
    use CPB\Utilities\Common\CollectionInterface;
    use CPB\Utilities\Common\Regex;
    
    class Map extends Collection
    {
        /**
         * Appends an iterable to the items
         *
         * the keys in the supplied iterable are de-duplicated,
         * duplicate keys are suffixed with '__%i', where %i is
         * the amount of times the key already has occurred
         */
        public function append(iterable $data): CollectionInterface
        {
            $keys = $this->keys();
        
            foreach($data as $key => $value)
            {
                $count = $keys->filter(
                    function($v) use($key){
                        return count(Regex::match(
                            \sprintf(
                                '/^%s(\(\d\))?/',
                                \str_replace('/', '\\/', \preg_quote($key))
                            ),
                            $v
                        )) > 0; }
                )->count();
            
                if($count > 0)
                {
                    $key .= '__(' . $count . ')';
                }
            
                $this[$key] = $value;
            }
        
            return $this;
        }
    
        /**
         * Sums all values by key
         */
//        public function sum(string $key = null)
//        {
//            return $this->columnAction('array_sum', $key ?? '');
//        }
    
        /**
         * Averages all values by key
         */
        public function average(string $key)
        {
            return $this->columnAction(function($arr){ return array_sum($arr) / count($arr); }, $key ?? '');
        }
    
        /**
         * Returns highest value by key
         *
         * optionally a upper bound can be set
         */
        public function max(string $key = null, float $limit = null)
        {
            $result = $this->columnAction('max', $key ?? '');
        
            if($limit !== null)
            {
                if($result instanceof static)
                {
                    $result = $result->map(function($k, $v) use($limit){ return min($v, $limit); });
                }
                else
                {
                    $result = min($result, $limit);
                }
            }
        
            return $result;
        }
    
        /**
         * Returns lowest value by key
         *
         * optionally a lower bound can be set
         */
        public function min(string $key = null, float $limit = null)
        {
            $result = $this->columnAction('min', $key ?? '');
        
            if($limit !== null)
            {
                if($result instanceof static)
                {
                    $result = $result->map(function($k, $v) use($limit){ return max($v, $limit); });
                }
                else
                {
                    $result = max($result, $limit);
                }
            }
        
            return $result;
        }
    
        /**
         * Returns value by key with lower and upper bound
         */
        public function clamp(string $key, float $lower, float $upper)
        {
            $result = $this->min($key, $lower);
            $map = function($k, $v) use($upper){ return min($v, $upper); };
        
            if($result instanceof static)
            {
                return $result->map($map);
            }
            else
            {
                return $map(null, $result);
            }
        }
    
        private function columnAction(callable $method, string $key, ...$args)
        {
            $items = $key === ''
                ? $this->items
                : $this->select($key);
        
            switch(true)
            {
                case $items instanceof static:
                    $items = $items->toArray();
                    break;
            
                case !\is_array($items):
                    $items = [ $items ];
                    break;
            }
        
            return $method($items, ...$args);
        }
    }
}
