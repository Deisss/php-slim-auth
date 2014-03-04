<?php
namespace Slim\Extras\Middleware;
use \Slim\Route;


/**
 * Abstract class to create a base for working with PHPSlim and HTTP BasicAuth
 * You need to extend this class to complete the getUserFromLogin method:
 * use this method to check authentification into database, and retrieve
 * important data for elements behind
*/
abstract class AbstractHTTPBasicAuth extends \Slim\Middleware {
    /**
     * List all path wich should be skipped for auth validation
    */
    private $skip = array();

    /**
     * Test the login and password exist, and should return the user you want
     * Any null/false/empty - use of php empty function - content will be
     * consider as Auth failing (refused by system)
     *
     * @param string $login The user submitted login
     * @param string $password The user submitted password
     *
     * @return object The corresponding userId, user object
     *                (what you want to use after)
    */
    abstract protected function getUserFromLogin($login, $password);





    /**
     * Fail the middleware
    */
    private function refuse() {
        $response = $this->app->response();
        // HTTP UNAUTHORIZE
        $response->status(401);
        $response->header('WWW-authentificate', 'Authentification required');
    }

    /**
     * succeed the middleware
    */
    private function accept() {
        $this->next->call();
    }





    /**
     * Erase the previous skipped list, and put only this one...
     *
     * @param array $skip The array to replace existing with
    */
    public function setSkip($skip) {
        if(is_array($skip)) {
            $this->skip = $skip;
        }
    }

    /**
     * Fully erase all elements into skip array.
    */
    public function eraseSkip() {
        $this->skip = array();
    }

    /**
     * Remove all trace of the given url from skip array.
     *
     * @param string $url The url to remove from skip
    */
    public function removeSkip($url) {
        $i = count($this->skip);

        while($i > 0) {
            $i--;
            if($this->skip[$i] == $url) {
                array_splice($this->skip, $i, 1);
            }
        }
    }

    /**
     * Add a new url to existing skipped list of urls
     *
     * @param string $url A new url to escape
    */
    public function addSkip($url) {
        $this->skip[] = $url;
    }

    /**
     * Get the current list of url skipped
     *
     * @return array The currently skipped array of urls
    */
    public function getSkip() {
        return $this->skip;
    }

    /**
     * Check if the given url should be skipped or not
     *
     * @param string $url The url to test
     * @return boolean True if it is register as skipped, false in other cases
    */
    public function isSkip($url) {
        foreach($this->skip as $skipped) {
            // Empty route to test matching
            $route = new \Slim\Route($skipped, function() {});

            if($route->matches($url)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Check HTTP Auth filter here
    */
    public function call() {
        // We test if url should be skipped or not
        $uri = $this->app->environment['PATH_INFO'];

        if($this->isSkip($uri)) {
            $this->accept();
            return;
        }

        // We start to handle request testing
        $request = $this->app->request();

        // Get the header info
        $login    = $request->headers()->get('Php-Auth-User');
        $password = $request->headers()->get('Php-Auth-Pw');

        // Refuse: auth is not setted properly
        if(empty($login) || empty($password)) {
            $this->refuse();
            return;
        }

        $result = $this->getUserFromLogin($login, $password);

        // Any refused user, or empty response, will be consider
        // as failing
        if(empty($result)) {
            $this->refuse();
            return;
        }

        // Publish data to request
        $this->app->environment['auth'] = $result;
        $this->app->environment['user'] = $result;
        $request->headers()->set('auth', $result);
        $request->headers()->set('user', $result);

        // Finishing request parsing
        $this->accept();
    }
}
?>