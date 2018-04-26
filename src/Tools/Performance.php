<?php

namespace CPB\Utilities\Tools
{
    final class Performance
    {
        private $start = 0;
        
        private function __construct()
        {
        }
        
        public static function start(): Performance
        {
            $inst = new static();
            $inst->start = microtime(true);
            
            return $inst;
        }
        
        public function stop(): void
        {
            echo \sprintf('<p>%sms</p>', (float)number_format(microtime(true) - $this->start, 10) * 1000);
        }
        
        public static function measure(callable $cb, int ...$repeat)
        {
            if(count($repeat) === 0)
            {
                $repeat[] = 1;
            }
            
            foreach($repeat as $iterations)
            {
                $inst = static::start();
    
                for($i = 0; $i < $iterations; $i++)
                {
                    $result = $cb($i);
                }
    
                $inst->stop();
            }
            
            return $result;
        }
    }
}
