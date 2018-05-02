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
        
        public function setDate($year, $month = null, $day = null): DateTime
        {
            $this->dayOfWeek = -1;
            
            if($year instanceof DateTime)
            {
                $month = $year->month;
                $day = $year->day;
                
                $year = $year->year;
            }
            
            return parent::setDate($year, $month, $day);
        }
    
        public function setDayOfWeek(int $day): DateTime
        {
            parent::setDate(1, 1, 1);
            $this->dayOfWeek = $day;
            
            return $this;
        }
    
        public function dayOfWeekIsset(): bool
        {
            return $this->dayOfWeek !== -1;
        }
    }
}
