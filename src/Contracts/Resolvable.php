<?php

namespace CPB\Utilities\Contracts
{
    interface Resolvable
    {
        public function resolve(string $key);
    }
}
