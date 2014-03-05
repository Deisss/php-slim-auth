# php-slim-auth

Simple, and yet powerfull middleware authentification for PHP Slim framework.


## Principle

We try threw this system to keep it as simple as possible. So we provide an authentification in two -extremely easy- parts:
  * We take care of authentification process, using HTTP Basic Auth
  * You extend this abstract class, to add your login/business logic to it.

Finally, you plug your extended class to Slim, and we are done.


## Installation

The system is using composer as main delivery system, using ```composer.json```:
```json
{
    "require": {
        "deisss/slim-auth": "dev-master"
    }
}
```

End then, recompile composer threw command line ```composer update```


## Usage

As login logic can be quite different from every system (like stateless, session based, facebook based), we decide it was necessary to provide a system where you can add your logic to it. An abstract authentification class was the best way to achieve this, and keep everything simple. So, as it's abstract, you need to create your own concrete class:

```php
<?php
namespace \Slim\Extras\Middleware;
using \Slim\Extras\Middleware\AbstractHTTPBasicAuth as AbstractHTTPBasicAuth;

/**
 * Our concrete authentification implementation
*/
class HTTPBasicAuth extends AbstractHTTPBasicAuth {
    /**
     * Constructor
     *
     * @param array $skipUrl Any revelant path to skip authentification check
    */
    public function __construct($skipUrl) {
        $this->setSkip($skipUrl);
    }

    /**
     * The function to handle the database/session/facebook check
     *
     * @param string $login The login supply by user
     * @param string $password The password supply by user
     * @param string $path The url user try to access
     * @return Any value 'null' for php empty function will be consider
     *         as a fail (and yet be refused), any non-empty value
     *         will be accepted
    */
    protected function getUserFromLogin($login, $password, $path) {
        // Your database/session check here

        // Any non-empty/false value will be consider as 'ok', we
        // recommand to send back full user object (as you can recover it later into route function - see below)
        return true;
    }
}
?>
```

Now this class is created (we consider the file name as ```HTTPBasicAuth.php```, we can use it:

```php
<?php
require 'vendor/autoload.php';
require 'HTTPBasicAuth.php'

$app = new \Slim\Slim();
$app->add(new \HTTPBasicAuth(array(
    '/hello/:name'
)));
$app->get('/hello/:name', function ($name) use ($app) {
    echo 'Hello $name';
});
$app->get('/logged', function() use ($app) {
    $userFromAuth = $app->request()->headers('auth');
    // Same
    $userFromAuth = $app->request()->headers('user');
});

$app->run();
?>
```


We show here a full example where we skip the auth for ```/hello/:name``` path (as you see skip handle same variable system as Slim). And for ```/logged``` path, we get back the user auth result from ```getUserFromLogin``` (so here, ```$userFromAuth === true```). You can of course return object instead of boolean ```getUserFromLogin```, to get full user !


## Furthermore

We didn't provide any ACL system, but regarding the Slim behavior, it can get quite simple to, using route middleware (Note: we consider same example as above):
```php
<?php
function isAdministrator() {
    $app = \Slim\Slim::getInstance();

    // userFromAuth is now a $_SESSION array instead of boolean value
    $userFromAuth = $app->request()->headers('auth');

    // We test, and refuse
    if($userFromAuth['role'] != 'administrator') {
        $response = $app->response();
        $response->status(403);
    }
}

$app->get('/this-is-acl', 'isAdministrator', function() {
?>
});
```


As you see, the ACL is also handled in a quick, and easy way, this is simply because a fact: the 'global' middleware, is perform BEFORE the 'route' middleware, allowing us to already know everything about user, inside the 'route' middleware one.


## License

Simple MIT License, hope you enjoy !

