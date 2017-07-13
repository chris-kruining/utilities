<?php

namespace spec\CPB\Utilities\Common
{
    use CPB\Utilities\Common\Collection;
    use PhpSpec\ObjectBehavior;

    class CollectionSpec extends ObjectBehavior
    {
        public function it_is_initializable()
        {
            $this->shouldHaveType(Collection::class);
        }

        public function it_is_stattically_initializable()
        {
            $this::From([])->shouldHaveType(Collection::class);
        }

        public function it_is_iterable()
        {
            $this->getIterator()->shouldHaveType(\Generator::class);
        }

        public function it_is_countable()
        {
            $this::From([
                'one',
                'two',
                'three',
            ])->count()->shouldReturn(3);
        }

        public function it_can_create_items()
        {
            $this['one'] = 1;
            $this['two'] = 2;
            $this['three'] = 3;

            $this->count()->shouldReturn(3);
        }

        public function it_can_read_items()
        {
            $this::From([
                'one',
                'two',
                'three',
            ])[1]->shouldReturn('two');
        }
    }
}
