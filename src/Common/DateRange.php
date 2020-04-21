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
                var_dump($this->periods->filter(fn($p) => $period->intersectsWith($p)));
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

        public function &iterator()
        {
            foreach($this->periods as $i => &$period)
            {
                yield $i => $period;
            }
        }

        public function getIterator()
        {
            yield from $this->periods
                ->reduce(fn($t, $k, $v) => \array_merge(
                    $t,
                    \iterator_to_array(new \DatePeriod($v->start, $this->interval, $v->end))
                ));
        }

        public function jsonSerialize()
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

        public function offsetExists($offset)
        {
            return key_exists($offset, $this->periods);
        }
        public function offsetGet($offset)
        {
            throw new NotImplemented;
        }
        public function offsetSet($offset, $value)
        {
            throw new NotImplemented;
        }
        public function offsetUnset($offset) {
            unset($this->periods[$offset]);
        }
    }
}
