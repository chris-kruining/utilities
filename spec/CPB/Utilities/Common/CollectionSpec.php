<?php

namespace spec\CPB\Utilities\Common
{
    use CPB\Utilities\Collections\Collection;
    use PhpSpec\ObjectBehavior;

    class CollectionSpec extends ObjectBehavior
    {
        public function it_is_initializable()
        {
            $this->shouldHaveType(Collection::class);
        }

        public function it_is_statically_initializable()
        {
            $this::from([])->shouldHaveType(Collection::class);
        }

        public function it_is_castable_to_array()
        {
            $this->toArray()->shouldBeArray();
        }

        public function it_is_castable_to_string()
        {
            $this::from([ 'one', 'two', 'three' ])
                ->toString(',')
                ->shouldReturn('one,two,three');
        }

        public function it_is_castable_to_object()
        {
            $this::from([ 'one', 'two', 'three' ])
                ->toObject(',')
                ->shouldBeLike((object)[ 'one', 'two', 'three' ]);
        }

        public function it_is_iterable()
        {
            $this->getIterator()->shouldHaveType(\Generator::class);
        }

        public function it_is_sortable()
        {
            $sorted = $this::from([ 21, 42, -38 ])->sort();

            $sorted->shouldHaveKeyWithValue(0, -38);
            $sorted->shouldHaveKeyWithValue(1, 21);
            $sorted->shouldHaveKeyWithValue(2, 42);
        }

        public function it_is_countable()
        {
            $this::from([ 'one', 'two', 'three' ])
                ->shouldHaveCount(3);
        }

        public function it_can_create_items()
        {
            $this['one'] = 1;
            $this['two'] = 2;
            $this['three'] = 3;

            $this->shouldHaveKeyWithValue('one', 1);
            $this->shouldHaveKeyWithValue('two', 2);
            $this->shouldHaveKeyWithValue('three', 3);
        }

        public function it_can_create_a_power_set()
        {
            $set = $this::from([ 'One', 'Two', 'Three' ])->powerSet()->toArray();
            
            $set->shouldContain([ 'One' ]);
            $set->shouldContain([ 'One', 'Two' ]);
            $set->shouldContain([ 'One', 'Three' ]);
            $set->shouldContain([ 'One', 'Two', 'Three' ]);
            $set->shouldContain([ 'Two' ]);
            $set->shouldContain([ 'Two', 'Three' ]);
            $set->shouldContain([ 'Three' ]);
        }

        public function it_can_read_items()
        {
            $this::from([
                'one',
                'two',
                'three',
            ])[1]->shouldReturn('two');
        }
    }
}
