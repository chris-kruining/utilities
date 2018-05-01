<?php

namespace CPB\Utilities\Common
{
    class DateTime extends \DateTime
    {
        private const
            FORMATS = [
                'N', 'H', 'i', 's'
            ],
            N = '[0-6]',
            H = '[0-2][0-9]',
            i = '[0-5][0-9]',
            s = '[0-5][0-9]'
        ;
        
        private $dayOfWeek = -1;
        
        public static function createFromFormat($format, $time, ?\DateTimeZone $timezone = null)
        {
            $regex = Regex::replace(
                \sprintf('/%s/', \join('|', self::FORMATS)),
                $format,
                function($m){ return sprintf('(?P<%s>%s)', $m[0], \constant('self::' . $m[0])); }
            );
            
            $matched = Regex::match(\sprintf('/%s/', $regex), $time);
            
            \var_dump($matched['N'] ?? -1);
    
            return parent::createFromFormat($format, $time, $timezone);
        }
    }
}
