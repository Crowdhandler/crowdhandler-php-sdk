<?php 

namespace CrowdHandler;

class Timer
{
    private $start;
    public function __construct()
    {
        $this->start = $this->getTimeinMS();
    }

    public function elapsed()
    {
        return $this->getTimeinMS() - $this->start;
    }

    private function getTimeInMs()
    {
         if (function_exists('hrtime')) {
            // hrtime is preferable for performance reasons.
           $output = hrtime(true);
           if (is_int($output)) {
               // 64 bit system, convert to float
               return $output/1e+6;
           } else {
               return $output*1000;
           }
       } else {
           // microtime is for backwards compatibilty 
           return microtime(true)*1000;
       }
    }
}

?>