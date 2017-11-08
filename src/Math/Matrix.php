<?php

namespace CPB\Utilities\Math
{
    use CPB\Utilities\Common\Collection;

    class Matrix implements \JsonSerializable
    {
        protected
            $data,
            $width,
            $height;

        public function __construct(int $width, int $height)
        {
            $this->data = new Collection;
            $this->width = $width;
            $this->height = $height;
        }

        public function __toString(): string
        {
            return $this->data
                ->map(function($k, $v)
                {
                    return $v->toString(',');
                })
                ->toString(',');
        }

        public function toArray(): array
        {
            return $this->data->reduce(function($t, $k, $v)
            {
                return array_merge($t, $v->toArray());
            });
        }

        public function jsonSerialize(): array
        {
            return $this->toArray();
        }

        public static function from(iterable $src, int $width, int $height): Matrix
        {
            $inst = new static($width, $height);
            $inst->data = Collection::from($src)
                ->chunk($width)
                ->slice(0, $height);

            return $inst;
        }
    }
}
