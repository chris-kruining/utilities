<?php

namespace CPB\Utilities\Common
{
    use CPB\Utilities\Common\Exceptions\NotImplemented;

    class DateRange implements \IteratorAggregate, \JsonSerializable, \ArrayAccess
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
                case 'years':
                case 'year':
                case 'y':
                    $format = '1Y';
                    break;

                case 'months':
                case 'month':
                case 'm':
                    $format = '1M';
                    break;

                case 'days':
                case 'day':
                case 'd':
                    $format = '1D';
                    break;

                case 'hours':
                case 'hour':
                case 'h':
                    $format = 'T1H';
                    break;

                case 'minutes':
                case 'minute':
                case 'i':
                    $format = 'T1M';
                    break;

                case 'seconds':
                case 'second':
                case 's':
                    $format = 'T1S';
                    break;

                case 'interval':
                    return $this->interval;

                case 'start':
                    return $this->periods->uSort(fn($a, $b) => $a->start <=> $b->start)->first()->start ?? null;

                case 'end':
                    return $this->periods->uSort(fn($a, $b) => $a->end <=> $b->end)->last()->end ?? null;

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

        public function add(DatePeriod ...$periods): DateRange
        {
            foreach($periods as $period)
            {
                \var_dump($this->periods->filter(fn(\DatePeriod $p) =>
                    $period->getEndDate() >= $p->getStartDate()
                    || $period->getStartDate() <= $p->getEndDate()
                ));
            }

            die;

            return $this;
        }

        public function subtract(DatePeriod ...$periods): DateRange
        {
            foreach($periods as $period)
            {
                foreach($this->periods->filter(fn($p) => $period->intersectsWith($p)) as $i => $p)
                {
                    // `$period` falls fully within `$p`
                    if($p->start < $period->start && $p->end > $period->end)
                    {
                        $this->periods->splice($i, 1, [
                            new DatePeriod($p->start, $period->start),
                            new DatePeriod($period->end, $p->end),
                        ]);
                    }
                    // `$period` has overlap on beginning of `$p`
                    elseif($p->start > $period->start && $p->end > $period->end)
                    {
                        $this->periods[$i]->start = $period->end;
                    }
                    // `$period` has overlap on beginning of `$p`
                    elseif($p->start < $period->start && $p->end < $period->end)
                    {
                        $this->periods[$i]->end = $period->start;
                    }
                    // `$period` is fully "covers" `$p`
                    elseif($p->start > $period->start && $p->end < $period->end)
                    {
                        unset($this->periods[$i]);
                    }
                }
            }

            return $this;
        }

        public function intersect(DateRange $range): DateRange
        {
            $inst = new static(...$this->periods
                ->each(function($k, $period) use($range){
                    yield from $range->periods
                        ->filter(fn($p) => $period->intersectsWith($p))
                        ->map(fn($k, $v) => $period->intersect($v));
                })
                ->uSort(fn($a, $b) => $a->start <=> $b->start)
            );

            $inst->interval = $this->interval;

            return $inst;
        }

        public function contains(DateTime $dateTime): bool
        {
            return $this->periods->some(fn($k, $p) => $p->contains($dateTime));
        }

        public function &iterator()
        {
            foreach($this->periods as $i => &$period)
            {
                yield $i => $period;
            }
        }

        public function getIterator(): \Traversable
        {
            yield from $this->periods
                ->reduce(fn($t, $k, $v) => \array_merge(
                    $t,
                    \iterator_to_array(new \DatePeriod($v->start, $this->interval, $v->end))
                ));
        }

        public function jsonSerialize(): mixed
        {
            return [
                'periods' => $this->periods->map(fn($k, $v) => [
                    'start' => $v->start->format('Y-m-d H:i'),
                    'end' => $v->end->format('Y-m-d H:i'),
                ]),
                'format' => 'Y-m-d H:i',
                'interval' => $this->interval->format('%r%Y-%M-%D %H:%I:%S'),
            ];
        }

        public function offsetExists(mixed $offset): bool
        {
            return key_exists($offset, $this->periods);
        }
        public function offsetGet(mixed $offset): mixed
        {
            throw new NotImplemented;
        }
        public function offsetSet(mixed $offset, mixed $value): void
        {
            throw new NotImplemented;
        }
        public function offsetUnset(mixed $offset): void
        {
            unset($this->periods[$offset]);
        }
    }
}
