<?php

namespace CPB\Utilities\Math
{
    use CPB\Utilities\Common\Collection;

    class Matrix
    {
        protected
            $data,
            $width,
            $height
        ;

        public function __construct(int $width, int $height)
        {
            $this->data = new Collection;
            $this->width = $width;
            $this->height = $height;
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
