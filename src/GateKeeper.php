<?php 

namespace CrowdHandler;

class GateKeeper
{
    const WAIT_URL = "https://wait.crowdhandler.com/";
    const HTTP_REDIRECT_CODE = 302;
    const TOKEN_COOKIE = 'ch-id';
    const TOKEN_URL = 'ch-id';

    const CROWDHANDLER_PARAMS = array(
        'ch-id',
        'ch-fresh',
        'ch-id-signature',
        'ch-public-key',
        'ch-requested',
        'ch-code'
    );

    private $ignore = "/^((?!.*\?).*(\.(avi|css|eot|gif|ico|jpg|jpeg|js|json|mov|mp4|mpeg|mpg|og[g|v]|pdf|png|svg|ttf|txt|wmv|woff|woff2|xml))$)/";
    private $client;
    private $failTrust = true;
    private $safetyNetSlug;
    private $debug = false;
    private $timer;
    private $cookieDomain;
    public $token;
    public $ip='192.168.0.1';
    public $agent='undetected';
    public $lang='undetected';
    public $url;
    public $result;
    public $redirectUrl;
 
    public function __construct(Client $client, \Psr\Http\Message\ServerRequestInterface $request=null) 
    {
        $this->timer = new Timer();
        $this->client = $client;
        if($request) {
        //  PSR7 
            $this->url = (string) $request->getUri()->withScheme('https');
            $get = $request->getQueryParams();
            $server = $request->getServerParams();
            $cookies = $request->getCookieParams();
        } else {
        //  Old School
            $this->url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $get = $_GET;
            $server = $_SERVER;
            $cookies = $_COOKIE;
        }

        $this->setCookieDomain($server);

        // Token in URL
        if (isset($get[self::TOKEN_URL])) {
            $this->setCookie($get[self::TOKEN_URL]);
            // clean url and redirect
            $this->sanitizeURL($this->url, $get);
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header('location: '.$this->url, true, self::HTTP_REDIRECT_CODE);
            exit;

        } elseif (isset($cookies[self::TOKEN_COOKIE])) {
            $this->token = $cookies[self::TOKEN_COOKIE];
        }

        $this->detectClientIp($server);
        if (isset($server['HTTP_USER_AGENT'])) $this->agent = $server['HTTP_USER_AGENT'];
        if (isset($server['HTTP_ACCEPT_LANGUAGE'])) $this->lang = $server['HTTP_ACCEPT_LANGUAGE'];        
    }

    /**
     * Removes crowdhandler specific query parameters on promotion
     * @param string $url The url that is currently being requested
     * @param array $get An array of the current query sring parameters   
     */
    private function sanitizeURL ($url, $get)
    {
        
        $parsed_url  = parse_url($url);
        $this->url = 'https://' . $parsed_url['host'] . $parsed_url['path'];

        $ch_params_to_remove = array();
        for ($i=0; $i < Count(self::CROWDHANDLER_PARAMS); $i++) {
            if (isset($get[self::CROWDHANDLER_PARAMS[$i]]))
            {
                array_push($ch_params_to_remove, $get[self::CROWDHANDLER_PARAMS[$i]]);
            }
        }

        $remaining_query_parameters = array_diff($get, $ch_params_to_remove);
        
        if (Count($remaining_query_parameters) > 0) {
            $this->url = $this->url .= '?' . http_build_query($remaining_query_parameters);
        }
       
    }

    private function detectClientIp($server)
    {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $server)) {
            $ip = $server["HTTP_X_FORWARDED_FOR"];
        } else if (array_key_exists('REMOTE_ADDR', $server)) {
            $ip = $server["REMOTE_ADDR"];
        } else if (array_key_exists('HTTP_CLIENT_IP', $server)) {
            $ip = $server["HTTP_CLIENT_IP"];
        }
    
        if (!empty($ip)) {
            $ip = explode(',', $ip);
            $this->ip = trim(reset($ip));
        }
   }

    public function setDebug($debug=false)
    {   
        $this->debug = $debug;
    }

    /**
     * Set trust user when checkUrl fails
     * @param boolean $trust true means trust user, false means sent to waiting room
     */
    public function setFailTrust($trust=false)
    {
        $this->failTrust = $trust;
    }

    /**
     * Set slug of fallback waiting room for bad requests/responses
     * @param string $slug Current URL
     */
    public function setSafetyNetSlug($slug)
    {
        $this->safetyNetSlug = $slug;
    }

    /**
     * Set CrowdHandler token manually
     * @param string $slug Current URL
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Detecting IPs can be hard - use this if the constructor is getting it wrong
     * @param string $ip IP Address
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * If you have your own regular exporession for urls to ignore set it here
     * @param string $regExp Regular Expression
     */
    public function setIgnoreUrls($regExp)
    {
        $this->ignore = $regExp;
    }

    private function debug($msg)
    {
       if ($this->debug) error_log($msg);
    }

    /**
     * Determine if the supplied URL should be ignored by CrowdHandler
     * @param string $url Current URL
     */
    private function ignoreUrl()
    {
        $matches = array();
        preg_match($this->ignore, $this->url, $matches);
        return count($matches) > 0;
    }

    /**
     * Get CrowdHandler response for current URL and token. 
     */
    public function checkRequest()
    {
        if($this->ignoreUrl()) {
            $mock = new ApiObject;
            $mock->status = 0;
            $mock->token = $this->token;
            $mock->position = null;
            $mock->promoted = 1;
            $this->result = $mock;
        } else {
            $params = array('url'=>$this->url, 'ip'=>$this->ip, 'agent'=>$this->agent, 'lang'=>$this->lang);
            try {
                if($this->token) {
                    $this->result = $this->client->requests->get($this->token, $params);
                } else {
                    $this->result = $this->client->requests->post($params);
                }
                if(isset($this->result->token)) {
                    $this->setCookie($this->result->token);
                }
            }
            catch (\Exception $e) {
                $mock = new ApiObject;
                $mock->status = 2;
                $mock->token = $this->token;
                $mock->position = null;
                $mock->slug = $this->safetyNetSlug; 
                $mock->promoted = $this->failTrust;     
                $this->result = $mock;
            }
        }
    }

    /**
     * Redirect user to waiting room if not promoted 
     */
    public function redirectIfNotPromoted()
    {
        if ($this->result->promoted!=1) {
            $this->getRedirectUrl();
            if ($this->debug) {
                $this->debug($this->redirectUrl);
            } else
            {
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                header('location: '.$this->redirectUrl, true, self::HTTP_REDIRECT_CODE);
                exit;
            }                
            
        }
    }

    /** 
     * Retrieve the URL this user should be redirected to
    */
    public function getRedirectUrl()
    {
        $params = array('url'=>$this->url, 'ch-public-key'=>$this->client->key, 'ch-id'=>$this->result->token);
        $this->redirectUrl = self::WAIT_URL.$this->result->slug.'?'.http_build_query($params);
        return $this->redirectUrl;
    }

    /**
     * Set Cookie domain based on server variables
     * Removes www. if found to allow subdomains 
     */
    private function setCookieDomain($server)
    {
        $host = "";
        if (array_key_exists('HTTP_HOST', $server)) {
            $host = $server["HTTP_HOST"];
            if(strpos($host, "www.") === 0) {
                $host = substr($host, 4);
            }
        }
        $this->cookieDomain = $host;
    }

    private function getCookieDomain()
    {
        return $this->cookieDomain;
    }

    /**
     * Set CrowdHandler session cookie 
     */
    private function setCookie($cookie)
    {   
        if (!is_null($cookie)) {
            setcookie(self::TOKEN_COOKIE, $cookie, 0, '/', $this->getCookieDomain(), $this->debug ? false: true);
            $this->debug('Setting cookie '.$cookie);
        }
    }

    /**
     * Send current page performance to CrowdHandler
     * @param integer $httpCode HTTP Response code  
     */
    public function recordPerformance($httpCode=200)
    {   
        if(@$this->result->responseID) {
            $time = $this->timer->elapsed();
            $this->client->responses->put($this->result->responseID, array('code'=>$httpCode, 'time'=>$time));
            $this->debug('Page performance was recorded '.$time);
        }
    }

}

