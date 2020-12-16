<?php

namespace CrowdHandler;

class Client
{
    const BASE_URL = "https://api.crowdhandler.com/v1/";
    const API_TIMEOUT = 2;

    public $key;

    /**
     * Client constructor.
     * @param CrowdHandler API Key $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    public function call($method, $resource, $params)
    {
        $resource = self::BASE_URL.$resource;
        if ($method == 'GET') {
            if ($params) $resource = $resource . "?" . http_build_query($params);
            $curl = curl_init($resource);
        } else {
            $curl = curl_init($resource);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            if (!defined('JSON_PRETTY_PRINT')) define('JSON_PRETTY_PRINT', 128);    // PHP 5.3 compatibility.
            if ($params) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params, JSON_PRETTY_PRINT));
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('content-type: application/json', 'x-api-key: ' . $this->key));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::API_TIMEOUT);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::API_TIMEOUT);
        $jsonResponse = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($responseCode == 200) {
            $response = json_decode($jsonResponse)->result;
            if (is_array($response)) {
                return new ApiArray($response);
            } else {
                return new ApiObject($response);
            }
            return json_decode($jsonResponse)->result;
        } else {
            throw new \Exception('API returned HTTP error '.$responseCode);
        }
    }
}

