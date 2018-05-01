<?php

namespace CPB\Utilities\Common
{
    class DateRange implements \IteratorAggregate
    {
        protected
            $periods;
    
        public function __construct(DatePeriod ...$periods)
        {
            $this->periods = \CPB\Utilities\Collections\Collection::from($periods);
        }
    
        public function __get($name)
        {
            switch($name)
            {
                case 'days':
                case 'day':
                case 'd':
                    $format = '1D';
                    break;
            
                default:
                    $format = $name;
                    break;
            }
            
            foreach($this->periods as $period)
            {
                yield from new \DatePeriod($period->start, new \DateInterval('P' . $format), $period->end);
            }
        }
        
        public function add(DatePeriod $period, DatePeriod ...$periods): DateRange
        {
            \array_unshift($periods, $period);
            
            foreach($periods as $period)
            {
                \var_dump($this->periods->filter(function(\DatePeriod $p) use($period){
                    return $period->getEndDate() >= $p->getStartDate() || $period->getStartDate() <= $p->getEndDate();
                }));
            }

            die;
            
            return $this;
        }
        
        public function intersect(DateRange $range): DateRange
        {
            
            
            return new static();
        }
    
        public function getIterator()
        {
            foreach($this->periods as $period)
            {
                yield from $period;
            }
        }
    }
}
