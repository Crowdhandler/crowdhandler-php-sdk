CrowdHandler PHP SDK
====================
PHP SDK for interacting with CrowdHandler Public and Private APIs. Extensive functionality for checking and queuing users

Instantiate a Public API client
--------------------------------

    $api = new CrowdHandler\PublicClient($yourPublicKey);

Instantiate a new GateKeeper object
-----------------------------------

#### request details implicit (classic PHP)

    $gatekeeper = new CrowdHandler\GateKeeper($api);
    
The GateKeeper class is a controller for interacting with the user request and the CrowdHandler API and taking appropriate action.

#### using PSR7 Request
    
    $request = new \Psr\Http\Message\ServerRequestInterface;
    $gatekeeper = new CrowdHandler\GateKeeper($api, request);

Out of the box, the GateKeeper inspects superglobals for url, ip, agent etc. 
But if you prefer, or your framework prefers, you can pass a PSR request.

Options
-------

#### Debug mode

    $gatekeeper->setDebug(true);
    
Will log some actions to the PHP error log, and also skip redirects so that you can see what's going on without being redirected constantly.
Inspect `$gatekeeper->getRedirectUrl()` to see where you would be redirected.

#### Ignore Urls

    $gatekeeper->setIgnoreUrls($regexp);
    
By default, common assets (png jpg etc) will be excluded from API checks, receiving automatic promotion. 
If you want you can pass your own regular expression. This will *override* the existing RegExp, so you will need to incorporate assets if necessary.

#### Failover waiting room    

    $gatekeeper->setSafetyNetSlug('yourslug');
    
By default, if an API call fails, or a malformed response is received, you will be redirected to CrowdHandler's ultimate catch-all waiting room until the API responds with more inforamtion. If you prefer to direct to your own, known, catch-all waiting room under these circumstances (recommended), you can set the slug here.

#### Go your own way

    $gatekeeper->setToken($_SESSION['token']);

By default GateKeeper will inspect and set cookies to retain the users session with CrowdHandler on your site. 
If you want to manage the session another way you can set the token yourself. 

#### IP detection getting it wrong? Set it yourself

    $gatekeeper->setIP($_SERVER['X-MY-WEIRD-LOADBALANCER-FORWARDS-THE-REAL-IP-LIKE-THIS']);
    
Tracking the user's IP should be a simple thing, but in load-balanced or cloud hosting environments, sometimes you'll get the IP of the load balancer instead of the IP of the user. GateKeeper tries common patterns to detect the IP, including common load balancer patterns, but you can ovverride what it detects by setting explicitly if your setup is more exotic. It's important to track the IP accurately. If the same user is tracked via two IPs they could be blocked erroneously, or simultaneously blocked and not-blocked, depending upon whether they are waiting or transacting. 

Check the current request
-------------------------
    
    $gatekeeper->checkRequest();
    
This is the heart of the class. It looks at the user's request, checks in with the API and retrieves a result that indicates whether the user should be granted access or be sent to a waiting room. 


Redirect the user if they should wait
-------------------------------------

#### Automatic

    $gatekeeper->redirectIfNotPromoted()
    
If this user should be waiting, they will be sent to the correct waiting room. There's no need for you to make a conditional check.

#### Do it yourself

    if (!$gatekeeper->result->promoted) {
        header('location: '.$gatekeeper->getRedirectUrl(), 302);
        exit;    
    }

If you want to make a conditional check, this is the check you shouild make, and how you can find the URL to redirect them to.

Set the cookie
--------------

#### Automatic

    $gatekeeper->setCookie();

Now you set the cookie so that the user carries their token with each request. 
This is important, if the token cannot be checked, a new one will be issued, and this may result in a promoted user being sent to a waiting room.

#### Go your own way

    $_SESSION['ch-id'] = $gatekeeper->result->token;

If you don't want to use the standard cookie, you can do your own thing. 

Instantiate a Private API Client
--------------------------------
    $api = new CrowdHandler\PrivateClient($yourPrivateKey);

Fetch an array of objects
-------------------------

#### All
    $rs = $api->rooms->get();

#### With parameters
    $rs = $api->rooms->get(['domainID'=>'dom_y0urk3y']);

#### Iterate
    foreach($rs as $room) print $room;


Fetch an object
---------------

    $room = $api->rooms->get('room_your1d');

Update an object
----------------

    $api->domains->put('dom_y0ur1d', ['rate'=>50, 'autotune'=>true]);

Post an object
--------------

    $api->templates->post(['name'=>'My Template', 'url'=>'https://mysite.com/wait.html']);

Delete an object
----------------

    $api->groups->delete('grp_y0ur1d')

More information
----------------

#### Knowledge base and API

https://support.crowdhandler.com

#### email

support@crowdhandler.com
