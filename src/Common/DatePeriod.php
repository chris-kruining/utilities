<?php

namespace CPB\Utilities\Common
{
    use CPB\Utilities\Common\Exceptions\NoIntersection;
    
    class DatePeriod implements \IteratorAggregate
    {
        public const
            DAYS = [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ]
        ;
        
        public
            $start,
            $end,
            $interval
        ;
        
        public function __construct(\DateTimeInterface $start, \DateTimeInterface $end, \DateInterval $interval = null)
        {
            $this->start = $start;
            $this->end = $end;
            $this->interval = $interval ?? new \DateInterval('P1D');
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
            return $this->start->dayOfWeek === $day || (clone $this->start)
                ->modify(\sprintf('next %s', self::DAYS[$day]))
                ->lte($this->end);
        }
        
        public function queryByDayOfWeek(int $day): ?DateTime
        {
            $date = $this->start->dayOfWeek === $day
                ? $this->start
                : (clone $this->start)->modify(\sprintf('next %s', self::DAYS[$day]));
            
            return $date->lte($this->end)
                ? $date
                : null;
        }
        
        public function intersectsWith(DatePeriod $b): bool
        {
            if($this->start->dayOfWeekIsset())
            {
                $this->start->setDate($b->queryByDayOfWeek($this->start->dayOfWeek));
            }
            elseif($b->start->dayOfWeekIsset())
            {
                $b->start->setDate($this->queryByDayOfWeek($b->start->dayOfWeek));
            }
            
            if($this->end->dayOfWeekIsset())
            {
                $this->end->setDate($b->queryByDayOfWeek($this->end->dayOfWeek));
            }
            elseif($b->end->dayOfWeekIsset())
            {
                $b->end->setDate($this->queryByDayOfWeek($b->end->dayOfWeek));
            }
            
            return $this->start->lte($b->end) && $this->end->gte($b->start);
        }
        
        public function merge(DatePeriod $b): DatePeriod
        {
            return new static(min($this->start, $b->start), max($this->end, $b->end));
        }
        
        public function intersect(DatePeriod $b): DatePeriod
        {
            if(!$this->intersectsWith($b))
            {
                throw new NoIntersection;
            }
    
            return new static(max($this->start, $b->start), min($this->end, $b->end));
        }
        
        public function getIterator()
        {
            yield from new \DatePeriod($this->start, $this->interval, $this->end);
        }
    }
}
