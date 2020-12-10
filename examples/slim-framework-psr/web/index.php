<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../../../vendor/autoload.php';

$app = new \Slim\App;
$app->get('/', function (Request $request, Response $response, array $args) {
    $api = new CrowdHandler\PublicClient('de50382842c3f4928adbc8ed9ab0518c27c883913fa790e163e0596f4b6445ed');
//  We pass Slim's PSR7 request instead of having GateKeeper corale info from CGI variables
    $ch = new CrowdHandler\GateKeeper($api, $request);
    $ch->checkRequest();    
//  We use Slim to set the cookie, not the method inside GateKeeper
    $cookies = new Slim\Http\Cookies();
    $cookies->set('ch-id', ['value' => $ch->result->token, 'path'=>'/']);
    if($ch->result->promoted) {
    //  Here you would build your response as normal. We're just rendering the CrowdHandler Result
        $response->getBody()->write($ch->result);
    //  Now log the performance, we're about to dispatch the response
        $ch->recordPerformance(200);
        return $response->withHeader('Set-Cookie', $cookies->toHeaders());
    } else {
        return $response->withRedirect($ch->getRedirectUrl(), 302);
    }
});
$app->run();