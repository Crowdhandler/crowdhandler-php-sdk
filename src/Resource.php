<?php

namespace CrowdHandler;

class Resource
{

    private $client;
    private $url;

    public function __construct(Client $client, $url)
    {
        $this->client = $client;
        $this->url = $url;
    }

    public function post($params=[])
    {
        return $this->client->call('POST', $this->url, $params);
    }

    public function get($id='', $params=[])
    {
        return $this->client->call('GET', $this->url.$id, $params);
    }

    public function put($id, $params=[])
    {
        return $this->client->call('PUT', $this->url.$id, $params);
    }

    public function delete($id, $params=[])
    {
        return $this->client->call('DELETE', $this->url.$id, $params);
    }

}

