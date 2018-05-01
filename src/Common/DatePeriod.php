<?php

namespace CPB\Utilities\Common
{
    class DatePeriod extends \DatePeriod
    {
        public static function instance(\DatePeriod $period)
        {
            $inst = new static;
            $inst->start = $period->start;
            $inst->end = $period->end;
            $inst->interval = $period->interval;
            
            return $inst;
        }
    }
}
