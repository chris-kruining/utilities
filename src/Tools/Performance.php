<?php

namespace CPB\Utilities\Tools
{
    final class Performance
    {
        private $start = 0;
        
        private function __construct()
        {
        }
        
        public static function Start()
        {
            $inst = new static();
            $inst->start = microtime(true);
            
            return $inst;
        }
        
        public function Stop()
        {
            var_dump((float)number_format(microtime(true) - $this->start, 10) * 1000 . 'ms');
        }
        
        public static function Measure(callable $cb)
        {
            $inst = static::Start();
            $result = $cb();
            $inst->Stop();
            
            return $result;
        }
    }
}
