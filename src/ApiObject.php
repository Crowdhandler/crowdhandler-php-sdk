<?php 

namespace CrowdHandler;

class ApiObject
{
    public function __construct($obj=null)
    {
        if ($obj) {
            foreach(get_object_vars($obj) as $attr=>$val) {
                $this->$attr = $val;
            }
        }
    }

    public function __toString()
    {
        return print_r($this, true);
    }
}