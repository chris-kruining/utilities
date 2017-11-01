<?php

namespace CPB\Utilities\Tools
{
    final class Performance
    {
        private $start = 0;
        
        private function __construct()
        {
        }
        
        public static function start()
        {
            $inst = new static();
            $inst->start = microtime(true);
            
            return $inst;
        }
        
        public function stop()
        {
            echo (float)number_format(microtime(true) - $this->start, 10) * 1000 . 'ms';
        }
        
        public static function measure(callable $cb, int $repeat = null)
        {
            $inst = static::start();
            
            if($repeat !== null)
            {
                for($i = 0; $i < $repeat; $i++)
                {
                    $result = $cb($i);
                }
            }
            else
            {
                $result = $cb(0);
            }
            
            $inst->stop();
            
            return $result;
        }
    }
}
