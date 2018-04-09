<?php

namespace CPB\Utilities\Common
{
    class DateRange implements \IteratorAggregate
    {
        protected
            $periods;
    
        public function __construct(\DatePeriod ...$periods)
        {
            $this->periods = \CPB\Utilities\Collections\Collection::from($periods);
        }
        
        public function add(\DatePeriod $period, \DatePeriod ...$periods): DateRange
        {
            \array_unshift($periods, $period);
            
//            foreach($periods as $period)
//            {
//                \var_dump($this->periods->filter(function(\DatePeriod $p) use($period){ $period->getEndDate() >= $p->getStartDate() || $period->getStartDate() <= $p->getEndDate(); }));
//            }
//
//            die;
            
            return $this;
        }
    
        public function getIterator()
        {
            foreach($this->periods as $period)
            {
                foreach($period as $dateTime)
                {
                    yield $dateTime;
                }
            }
        }
    }
}
