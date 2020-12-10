<?php

namespace CrowdHandler;

class PrivateClient extends Client
{
    /**
     * PublicClient constructor.
     * @param CrowdHandler Private API Key $key
     */
    public function __construct($key)
    {
        parent::__construct($key);
        $this->account = new Resource($this, 'account/');
        $this->codes = new Resource($this, 'codes/');
        $this->domains = new Resource($this, 'domains/');
        $this->groups = new Resource($this, 'groups/');
        $this->ips = new Resource($this, 'ips/');
        $this->reports = new Resource($this, 'reports/');
        $this->rooms = new Resource($this, 'rooms/');
        $this->sessions = new Resource($this, 'sessions/');
        $this->templates = new Resource($this, 'templates/');
    }
}

