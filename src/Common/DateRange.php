<?php

namespace CPB\Utilities\Common
{
    use function MongoDB\BSON\fromJSON;
    
    class DateRange implements \IteratorAggregate
    {
        protected
            $periods,
            $interval
        ;
    
        public function __construct(DatePeriod ...$periods)
        {
            $this->periods = \CPB\Utilities\Collections\Collection::from($periods);
            $this->interval = new \DateInterval('P1D');
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
                    
                case 'interval':
                    return $this->interval;
            
                default:
                    $format = $name;
                    break;
            }
            
            $this->interval = new \DateInterval('P' . $format);
            
            return $this;
        }
    
        public function __set($name, $value)
        {
            switch($name)
            {
                case 'interval':
                    $this->interval = $value;
                    break;
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
            $inst = new static(...$this->periods
                ->each(function($k, $period) use($range){
                    yield from $range->periods
                        ->filter(function($p) use($period){ return $period->intersectsWith($p); })
                        ->map(function($k, $v) use($period){ return $period->intersect($v); });
                })
                ->uSort(function($a, $b){ return $a->start <=> $b->start; })
            );
            
            $inst->interval = $this->interval;
            
            return $inst;
        }
    
        public function getIterator()
        {
            yield from $this->periods->reduce(
                function($t, $k, $v){
                    return \array_merge($t, \iterator_to_array(new \DatePeriod($v->start, $this->interval, $v->end)));
                },
                []
            );
        }
    }
}
