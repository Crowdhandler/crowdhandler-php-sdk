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
 
    public function __construct(Client $client, ?\Psr\Http\Message\ServerRequestInterface $request = null) 
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
            //  Not every SAPI populates these (CLI, cron and some FastCGI setups
            //  omit them). Read them defensively rather than warn; sanitizeURL()
            //  guards the resulting url before it is ever used for a redirect.
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $this->url = 'https://'.$host.$uri;
            $get = $_GET;
            $server = $_SERVER;
            $cookies = $_COOKIE;
        }

        $this->setCookieDomain($server);

        // Token in URL
        $urlToken = $this->getUrlToken($get);
        if (!is_null($urlToken)) {
            $this->setCookie($urlToken);
            // clean url and redirect
            $this->sanitizeURL($this->url, $get);
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header('location: '.$this->url, true, self::HTTP_REDIRECT_CODE);
            exit;

        } else {
            $cookieToken = $this->getCookieToken($cookies);
            if (!is_null($cookieToken)) {
                $this->token = $cookieToken;
            }
        }

        $this->detectClientIp($server);
        if (isset($server['HTTP_USER_AGENT'])) $this->agent = $server['HTTP_USER_AGENT'];
        if (isset($server['HTTP_ACCEPT_LANGUAGE'])) $this->lang = $server['HTTP_ACCEPT_LANGUAGE'];        
    }

    /**
     * Read the CrowdHandler token from the query parameters.
     * Accepts both the canonical hyphenated key ('ch-id') and the
     * underscored variant ('ch_id') that some proxies/frameworks produce.
     * @param array $get An array of the current query string parameters
     * @return string|null The token value, or null if not present
     */
    private function getUrlToken($get)
    {
        return $this->readToken($get, self::TOKEN_URL);
    }

    /**
     * Read the CrowdHandler token from the request cookies.
     * Accepts both the canonical hyphenated name ('ch-id') and the
     * underscored variant ('ch_id') that some proxies/frameworks produce.
     * @param array $cookies An array of the current request cookies
     * @return string|null The token value, or null if not present
     */
    private function getCookieToken($cookies)
    {
        return $this->readToken($cookies, self::TOKEN_COOKIE);
    }

    /**
     * Look up a token by key, falling back to the underscored variant of that
     * key. Only scalar values are accepted, so array input (e.g. 'ch-id[]=a')
     * is treated as absent rather than propagating into setcookie().
     * @param array $source The array to read from
     * @param string $key The canonical hyphenated key
     * @return string|null The token value, or null if not present
     */
    private function readToken($source, $key)
    {
        $keys = array($key, str_replace('-', '_', $key));
        foreach ($keys as $candidate) {
            if (isset($source[$candidate]) && is_scalar($source[$candidate])) {
                return (string) $source[$candidate];
            }
        }
        return null;
    }

    /**
     * Removes crowdhandler specific query parameters on promotion
     * @param string $url The url that is currently being requested
     * @param array $get An array of the current query sring parameters
     */
    private function sanitizeURL ($url, $get)
    {

        $parsed_url  = parse_url($url);
        // With no Host header there is no origin to rebuild: parse_url() returns
        // false and reading ['host'] off it would warn, breaking the header()
        // call that follows. Leave the url untouched instead.
        if (!is_array($parsed_url) || !isset($parsed_url['host'])) {
            return;
        }
        // parse_url() returns the port separately and omits 'path' entirely for
        // urls like 'https://example.com?ch-id=x', so rebuild defensively:
        // dropping the port would redirect to the wrong origin, and a missing
        // path would emit a warning that breaks the subsequent header() call.
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
        $this->url = 'https://' . $parsed_url['host'] . $port . $path;

        // Strip every CrowdHandler param by key, covering both the hyphenated
        // form ('ch-id') and the underscored form ('ch_id') that some
        // proxies/frameworks produce, so none leak back into the clean URL.
        $remaining_query_parameters = $get;
        foreach (self::CROWDHANDLER_PARAMS as $param) {
            unset($remaining_query_parameters[$param]);
            unset($remaining_query_parameters[str_replace('-', '_', $param)]);
        }

        $remaining_query_parameters['ch-fresh'] = uniqid();
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
        if(isset($this->result) && property_exists($this->result, 'responseID')) {
            $time = $this->timer->elapsed();
            $this->client->responses->put($this->result->responseID, array('code'=>$httpCode, 'time'=>$time));
            $this->debug('Page performance was recorded '.$time);
        }
    }

}

