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

#### using PSR7 Request
    
    $request = new \Psr\Http\Message\ServerRequestInterface;
    $gatekeeper = new CrowdHandler\GateKeeper($api, request);


Options
-------

#### Debug mode

    $gatekeeper->setDebug(true);

#### Ignore Urls

    $gatekeeper->setIgnoreUrls(regexp);

#### Failover waiting room    

    $gatekeeper->setSafetyNetSlug('yourslug');

#### Go your own way

    $gatekeeper->setToken($_SESSION['token']);

#### IP detection getting it wrong? Set it yourself

    $gatekeeper->setIP($_SERVER['X-MY-WEIRD-LOADBALANCER-FORWARDS-THE-REAL-IP-LIKE-THIS']);


Check the current request
-------------------------
    
    $gatekeeper->checkRequest()


Redirect the user if they should wait
-------------------------------------

#### Automatic

    $gatekeeper->redirectIfNotPromoted()

#### Do it yourself

    if (!$gatekeeper->result->promoted) {
        header('location: '.$gatekeeper->getRedirectUrl(), 302);
        exit;    
    }


Set the cookie
--------------

#### Automatic

    $gatekeeper->setCookie();

#### Go your own way

    $_SESSION['ch-id'] = $gatekeeper->result->token;



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