<?php 

namespace CrowdHandler;

class GateKeeper
{
    const WAIT_URL = "https://wait.crowdhandler.com/";
    const HTTP_REDIRECT_CODE = 302;
    const TOKEN_COOKIE = 'ch-id';
    const TOKEN_URL = 'ch-id';

    private $ignore = "/^.*\.(ico|css|js|json|pdf|xml|eot|ott|ttf|woff|woff2|gif|jpg|png|svg|avi|mov|mp4|mpeg|mpg|wmv|ogg|ogv)$/";
    private $client;
    private $failTrust = false;
    private $safetyNetSlug;
    private $debug = false;
    private $timer;
    public $token;
    public $ip;
    public $agent;
    public $lang;
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
        if (isset($get[self::TOKEN_URL])) {
            $this->token = $get[self::TOKEN_URL];
        } elseif (isset($cookies[self::TOKEN_COOKIE])) {
            $this->token = $cookies[self::TOKEN_COOKIE];
        }
        $this->ip = $server['REMOTE_ADDR'];
        $this->agent = $server['HTTP_USER_AGENT'];
        $this->lang = $server['HTTP_ACCEPT_LANGUAGE'];        
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

    public function setIgnoreUrls($regExp)
    {
        $this->ignore = $refExp;
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
        $url = explode('?', $this->url)[0];
        preg_match($this->ignore, $url, $matches, PREG_UNMATCHED_AS_NULL);
        return $matches != null;
    }

    /**
     * Get CrowdHandler response for current URL and token. 
     */
    public function checkRequest()
    {
        if($this->ignoreUrl()) {
            $mock = new ApiObject;
            $mock->status = null;
            $mock->position = null;
            $mock->promoted = 1;
            $this->result = $mock;
        } else {
            $params = ['url'=>$this->url, 'ip'=>$this->ip, 'agent'=>$this->agent, 'lang'=>$this->lang];
            try {
                if($this->token) {
                    $this->result = $this->client->requests->get($this->token, $params);
                } else {
                    $this->result = $this->client->requests->post($params);
                }    
            }
            catch (Exception $e) {
                $mock = new ApiObject;
                $mock->status = null;
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
                header('location: '.$this->redirectUrl, true, self::HTTP_REDIRECT_CODE);
                exit;
            }                
            
        }
    }

    public function getSanitisedTargetURL()
    {
        # Split the full URL down so that we can manipulate and reconstruct later. 
        $scheme = parse_url($this->url, PHP_URL_SCHEME);
        $host = parse_url($this->url, PHP_URL_HOST);
        $path = parse_url($this->url, PHP_URL_PATH);
        $queryString = parse_url($this->url, PHP_URL_QUERY);
        parse_str($queryString, $output);

        $params = $output;
        if(count($params) > 0) {
            # We don't want to have to deal with nested versions of this special param key in the waiting room.
            unset($params['ch-id']);
            if(count($params) > 0) {
                return $scheme . "://" . $host . $path . "?" . http_build_query($params);
            } else {
                return $scheme . "://" . $host . $path;
            }
        } else {
            return $scheme . "://" . $host . $path;
        }
    }

    public function getRedirectUrl()
    {
        $waitParams = ['url'=>$this->getSanitisedTargetURL(), 'ch-public-key'=>$this->client->key, 'ch-id'=>$this->result->token];
        $this->redirectUrl = self::WAIT_URL.$this->result->slug.'?'.http_build_query($waitParams);
        return $this->redirectUrl;
    }

    /**
     * Set CrowdHandler session cookie 
     */
    public function setCookie()
    {
        setcookie(self::TOKEN_COOKIE, $this->result->token, 0, '/', '', $this->debug ? false: true);
        $this->debug('Setting cookie '.$this->result->token);
    }

    /**
     * Send current page performance to CrowdHandler
     * @param integer $httpCode HTTP Response code  
     */
    public function recordPerformance($httpCode=200)
    {   
        if(@$this->result->responseID) {
            $time = $this->timer->elapsed();
            $this->client->responses->put($this->result->responseID, ['code'=>$httpCode, 'time'=>$time]);
            $this->debug('Page performance was recorded '.$time);
        }
    }

}

