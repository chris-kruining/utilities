<?php

namespace CPB\Utilities\Common
{
    use CPB\Utilities\Collections\Table;
    
    final class Collection extends Table
    {
        /** @deprecated In favor of an inheritance structure */
        public function __construct()
        {
            parent::__construct();
            
//            \trigger_error(\sprintf(\join('', [
//                '%s is deprecated in favor of an inheritance structure to shrink the classes,',
//                'use \'CPB\Utilities\Collections\*\' instead'
//            ]), static::class), \E_USER_WARNING);
        }
    
        /**
         * @deprecated Renamed to 'includes'
         */
        public function contains($value): bool
        {
            return $this->includes($value);
        }
    
        /**
         * @deprecated In favor of an inheritance structure
         */
        public static function from(iterable $items): CollectionInterface
        {
            return parent::from($items);
        }
    }
}
