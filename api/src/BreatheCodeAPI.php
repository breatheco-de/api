<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Api\Src\Handlers;

use Chadicus\Slim\OAuth2\Routes;
use Chadicus\Slim\OAuth2\Middleware;
use Slim\Http;
use Slim\Views;
use OAuth2\Storage;
use OAuth2\GrantType;
use Helpers\ExtendedPDO;

class BreatheCodeAPI
{
    /**
     * Stores an instance of the Slim application.
     *
     * @var \Slim\App
     */
    private $app;
    private $server;
    private $scopes;

    public function __construct($config=null) {

        $this->app = new \Slim\App($config);
        $this->app->options('/{routes:.+}', function ($request, $response, $args) {
            return $response;
        });
        $this->app->add(function ($req, $res, $next) {
            $response = $next($req, $res);
            return $response
                    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-PINGOTHER')
                    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
                    ->withHeader('Allow', 'GET, POST, PUT, DELETE');
        });

        // Bootstrap Eloquent ORM
        $container = new Illuminate\Container\Container();
        $this->app->db = $this->getNewConnection($container, $config['settings']['db']);

        $resolver = new \Illuminate\Database\ConnectionResolver();
        $resolver->addConnection('default', $this->app->db);
        $resolver->setDefaultConnection('default');
        \Illuminate\Database\Eloquent\Model::setConnectionResolver($resolver);

        $forceAllScopes = false;
        if(isset($config['authenticate']) && $config['authenticate'] == false){
            $forceAllScopes = true;
        }
        $this->server = $this->startOAuthServer($config['settings']['db']);
        $this->authorization = $this->authorization($forceAllScopes);

    }

    public function getContainer(){
        return $this->app->getContainer();
    }

    private function getNewConnection($container, $config){
        $connFactory = new \Illuminate\Database\Connectors\ConnectionFactory($container);
        return $connFactory->make($config);
    }

    private function startOAuthServer($config){

        /**
         * OAuth 2.0 implementation
         * Using externarl library: https://github.com/chadicus/slim-oauth2
         * And PHP Oauth Server: https://bshaffer.github.io/oauth2-server-php-docs/
         **/
        $conectionInfo = [];
        if($config['driver']=='mysql')
        {
            $conectionInfo['dsn'] = 'mysql:host=localhost;dbname='.$config['database'];
            $conectionInfo['username'] = $config['username'];
            $conectionInfo['password'] = $config['password'];
        }
        else if($config['driver']=='sqlite')
        {
            $conectionInfo['dsn'] = 'sqlite:'.$config['database'];
        }

        $storage = new ExtendedPDO($conectionInfo);
        $this->app->storage = $storage;

        $request = OAuth2\Request::createFromGlobals();
        $server = new OAuth2\Server($storage,array(
            'access_lifetime' => (3600*24) //one day
        ));

        //print_r($request->request); die();
        if (isset($request->request['expires_in'])){
            // one year would be 315,00,000, one day 86,400, one hour 3600
            $server->setConfig('access_lifetime',  (int)$request->request['expires_in']);
        }
        else if (isset($request->request['grant_type']) && $request->request['grant_type'] == "client_credentials"){
            $server->setConfig('access_lifetime', 3600 * 24 * 365); //one year
        }

        //Enable Authorization Code credentials to allow request from authorization code.
        $server->addGrantType(new GrantType\AuthorizationCode($storage));
        //Enable ClientCredentials to allo clients to generate an authorization code.
        $server->addGrantType(new GrantType\ClientCredentials($storage));
        //Enable user login form
        $server->addGrantType(new GrantType\UserCredentials($storage));

        $getUsernameMiddleware = function ($request, $response, $next) {

            $body = $request->getParsedBody();

            if(isset($body['username']))
            {
                $user = User::where('username', $body['username'])->first();
                //print_r($body); die();
                if(!$user) throw new Exception('Invalid credentials for: '.$body['username'], 403);
            }

            $response = $next($request, $response);//do the next middleware layer action

            return $response;
        };

        //middleware to add user_id to route parameters
        $app = $this->app;
        $getUserIdMiddleware = function ($request, $response, $next) use ($server, $app){
        	// Validate the user credentials
        	$authRequest = \OAuth2\Request::createFromGlobals();
        	$data = $server->getAccessTokenData($authRequest);

            if(empty($data['access_token'])) return $next($request, $response);

        	$user = User::where('username', $data['user_id'])->first();
        	if (!$user) return $next($request, $response);

        	$data["user"] = $user;

        	//Put user_id into the route parameters
        	$c = $app->getContainer();
        	$c["token_data"] = $data;

        	//Credentials are valid, continue so the authorization code can be sent to the clients callback_uri
        	return $next($request, $response);
        };
        $this->app->add($getUserIdMiddleware);

        //The HTML views for the OAuth Autentication process
        $renderer = new Views\PhpRenderer( __DIR__ . '/vendor/chadicus/slim-oauth2-routes/templates');
        $this->app->map(['GET', 'POST'], Routes\Authorize::ROUTE, new Routes\Authorize($server, $renderer))->setName('authorize');
        $this->app->post(Routes\Token::ROUTE, new Routes\Token($server))->setName('token')->add($getUsernameMiddleware);
        $this->app->map(['GET', 'POST'], Routes\ReceiveCode::ROUTE, new Routes\ReceiveCode($renderer))->setName('receive-code');
        //Creating the Middleware to intercept all request and ask for authorization before continuing

        return $server;
    }

    private function authorization($forceAllScopes = false){

        $this->scopes = function($posibleScopes) use ($forceAllScopes){

            if(!is_array($posibleScopes)) throw new Exception('The possible scopes must be an array');
            foreach($posibleScopes as $s) if(!in_array($s, GLOBAL_CONFIG['scopes']))  throw new Exception('Invalid scope type: '.$s);

            if($forceAllScopes || count($posibleScopes)==0) return $this->authorization;

            return $this->authorization->withRequiredScope(array_merge($posibleScopes,['super_admin']));

        };

        if($forceAllScopes) return function ($request, $response, $next) {
            return $next($request, $response);
        };
        else return new Middleware\Authorization($this->server, $this->app->getContainer());
    }

    public function addRoutes($globalRoutes){

        foreach($globalRoutes as $route){
            $className = "Routes\\".ucfirst($route)."Routes";
            $r = new $className($this->app, $this->scopes);
        }

    }


    /**
     * Get an instance of the application.
     *
     * @return \Slim\App
     */
    public function run(){
        // Catch-all route to serve a 404 Not Found page if none of the routes match
        // NOTE: make sure this route is defined last
        $this->app->map(['GET', 'POST', 'PUT', 'DELETE'], '/{routes:.+}', function($req, $res) {
            $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
            return $handler($req, $res, new Exception('Endpoint not found', 404));
        });

        return $this->app->run();
    }

}