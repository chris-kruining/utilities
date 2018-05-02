<?php

namespace CPB\Utilities\Common
{
    use Carbon\Carbon;
    
    class DateTime extends Carbon
    {
        private $dayOfWeek = -1;
        
        public function __get($name)
        {
            if($name=== 'dayOfWeek' && $this->dayOfWeek !== -1)
            {
                return $this->dayOfWeek;
            }
            
            return parent::__get($name);
        }
    
        public function setDayOfWeek(int $day): DateTime
        {
            $this->setDate(1, 1, 1);
            $this->dayOfWeek = $day;
            
            return $this;
        }
    }
}
