<?php 

namespace CrowdHandler;

class ApiArray implements \Iterator 
{
    private $position = 0;
    private $array;

    public function __construct($array) 
    {
        $this->array = $array;
        $this->position = 0;
    }

    public function rewind() 
    {
        $this->position = 0;
    }

    public function current() 
    {
        return new ApiObject($this->array[$this->position]);
    }

    public function key() 
    {
        return $this->position;
    }

    public function next() 
    {
        ++$this->position;
    }

    public function valid() 
    {
        return isset($this->array[$this->position]);
    }

    public function __toString()
    {
        return print_r($this, true);
    }

}