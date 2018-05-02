<?php

namespace CPB\Utilities\Common
{
    use Traversable;
    
    class DatePeriod implements \IteratorAggregate
    {
        public
            $start,
            $end,
            $interval
        ;
        
        public function __construct(\DateTimeInterface $start, \DateInterval $interval, \DateTimeInterface $end, int $options = 0)
        {
            $this->start = $start;
            $this->end = $end;
            $this->interval = $interval;
        }
    
        public static function instance(\DatePeriod $period)
        {
            return new static(
                DateTime::instance($period->start),
                $period->interval,
                DateTime::instance($period->end)
            );
        }
        
        public function containsDayOfWeek(int $day): bool
        {
            return $this->start->dayOfWeek <= $day && $this->end->dayOfWeek >= $day;
        }
        
        public function getIterator()
        {
            yield from new \DatePeriod($this->start, $this->interval, $this->end);
        }
    }
}
