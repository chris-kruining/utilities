<?php

namespace CPB\Utilities\Tools
{
    use CPB\Utilities\Collections\Collection;

    final class Performance
    {
        private
            $start = 0,
            $marks = []
        ;

        private function __construct()
        {
        }

        public static function start(): Performance
        {
            $inst = new static();
            $inst->start = microtime(true);

            return $inst;
        }

        public function mark(int $offset = 0): void
        {
            $this->marks[] = [
                microtime(true),
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $offset + 1)[$offset] ?? []
            ];
        }

        public function stop(int $offset = 1): void
        {
            $this->mark($offset);
            $last = $this->start;

            foreach($this->marks as [ $mark, $debug ])
            {
                echo \sprintf(
                    '<p>%sms - %s::%s</p>',
                    (float)number_format($mark - $last, 10) * 1000,
                    $debug['file'],
                    $debug['line']
                );

                $last = $mark;
            }
        }

        public function any(callable $callback): bool
        {
            $l = $this->start;

            return Collection::from($this->marks)
                ->map(function($k, $v) use(&$l){
                    $r = (float)number_format($v[0] - $l, 10) * 1000;
                    $l = $v[0];
                    return $r;
                })
                ->some(fn($k, $v) =>$callback($v));
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
                    $result = $cb($i, $inst);
                }

                $inst->stop(2);
            }

            return $result;
        }
    }
}
