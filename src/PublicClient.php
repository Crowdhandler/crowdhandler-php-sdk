<?php

namespace CrowdHandler;

class PublicClient extends Client
{
    /**
     * PublicClient constructor.
     * @param CrowdHandler Public API Key $key
     */
    public function __construct($key)
    {
        parent::__construct($key);
        $this->requests = new Resource($this, 'requests/');
        $this->responses = new Resource($this, 'responses/');
    }
}

