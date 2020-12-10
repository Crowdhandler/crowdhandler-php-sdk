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
    $gatekeeper = new CrowdHandler\GateKeeper($api, $request);


Options
-------

#### Debug mode

    $gatekeeper->setDebug(true);

When debug mode is true, some information will be logged to the PHP error log, and redirects will not occur (although you can still check getRedirectUrl()). This will assist you with configuring and debugging. 

#### Ignore Urls

    $gatekeeper->setIgnoreUrls($regexp);

Check the GateKeeper class for the existing Regular Expression for excluding assets and similar files. You can replace this with your own Regular Expression using this option. Take care to re-include standard asset file-types if you need to, as you will override the existing expression. Requests for excluded URLs will skip the API call and return result->promoted = 1.

#### Failover waiting room    

    $gatekeeper->setSafetyNetSlug('yourslug');

If you have a catch-all waiting room set up on your domain, you can speficy the slug here. Users will be directed to this waiting room in the case of a failed API call. 

#### Go your own way

    $gatekeeper->setToken($_SESSION['token']);

Standard behavior is to look for the query parameter ch-id or failing that a cookie with the key ch-id. If you want to do something else, you can set the token yourself. If you're using an alternative to the cookie, you'll need to make sure you set that too. 

Check the current request
-------------------------
    
    $gatekeeper->checkRequest();

This will inspect the request and set a result attribute on the gatekeeper class, which you can inspect. The following method will inspect the result for you and take appropriate action.

Redirect the user if they should wait
-------------------------------------

#### Automatic

    $gatekeeper->redirectIfNotPromoted();

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

    $room = $api->rooms->get('wrm_your1d');

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
