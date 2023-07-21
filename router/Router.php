<?php

require 'HttpMethods.php';

class Router
{
    use HttpMethods;
    
    //The array of all registered routes
    protected array $routes = [];

    //The params of the current route
    protected array $params = [];

    //the query params of the current route
    protected array $queryParams = [];

    //The request body
    protected $body;

    //The body in case of form post request
    protected array $formBody = [];

    //The variable to ckeck if it is a uri with params.
    private bool $uriHasParams = false;

    /*
     The method that actually runs the entire router.
    */
    public function run() {

        //We remove the forward slashes "/" before and after, and we sanitize it.
        $initialUri = htmlspecialchars(trim($_SERVER['REQUEST_URI'], "/"), ENT_QUOTES, 'UTF-8');

        //We remove any queries from the client's uri if they exist and remove the slashes before and after.
        $uri = trim(preg_replace('/\w+\?\w+=\w+(&\w+=\w+)*$/' ,'', $initialUri), '/');
        
        // If there are query params, we extract them and put the in the queryParams array.
        $this->extractQueryParams($initialUri, $uri);         

        //We examine each registered route one by one
        foreach($this->routes as $registeredUri => $handler) {          

            // first we check if the route exists (sanitised and case insensitive).
            if($uri === $registeredUri) {
              if($handler['method'] === $_SERVER['REQUEST_METHOD']) {  

                
                $this->extractRequestBody($handler['method']);
                $this->extractFormBody($handler['method']);
                $this->handleBasicRoute($uri, $handler);

                return;
              }
            }
            
            //We check if it matches, e.g. '/user/{id}/name{id}. If 
            //it is true, we check if the current uri corresponds to the 
            //registered one.
            if(preg_match_all('/{(\w+)}/', $registeredUri, $matches)) {
                
                $this->extractRequestBody($handler['method']);
                $this->extractFormBody($handler['method']);
                $this->handleUriWithParams($registeredUri, $uri, $handler);                 
            }    
            
            //If it matched a uri with params, we stop the method from further executing.
            if($this->uriHasParams)
                return;
        }
        echo 'Not Found';
    }

    /*
        The methods below are tools to be used in the run method.
    */

    //The case of simple uri without params
    private function handleBasicRoute($uri, $handler) {
        
        echo $uri;    
        echo $handler['controller'];
    }
    
    //The method below checks if the uri has params. if true, 
    // we save them in the $params array
    private function handleUriWithParams($key, $uri, $handler) {

        //We replace the {} part with capturing naming group.
        $pattern = preg_replace('/{(\w+)}/', '(?P<$1>[^/]+)', $key);
                        
        //We concatonate the pattern and assign it to the route
        $routePattern = "#^$pattern$#";

        //We now check if it matches the $uri and the request method
        if (preg_match($routePattern, $uri, $matches) && $_SERVER['REQUEST_METHOD']) {

            //From the matches array, we keep only the string keys, which are the params.
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->params[$key] = $value;
                }
            }

            $this->uriHasParams = true;

            echo $handler['controller'];
        }
    }

    //The method to extract any query params if they exist
    private function extractQueryParams($initialUri, $uri) {

        //we isolate the query string without the first part (word?)
        if(preg_match('/\w+\?\w+=\w+(&\w+=\w+)*$/', $initialUri)) {

            $queryString = preg_replace('/\w+\?/', '', str_replace($uri.'/', '', $initialUri));
            $queryArray = explode('&',$queryString);
        

         foreach($queryArray as $query) {

            $keyValuePair = explode('=', $query);

            $this->queryParams[$keyValuePair[0]] = $keyValuePair[1];
          }
          print_r($this->queryParams);
        }
    }

    //The method to extract the body for any HTTP method that supports body
    private function extractRequestBody($method) {
        
        if ( $method === 'POST' || $method  === 'PUT' || $method  === 'PATCH' || 
             $method  === 'DELETE' || $method  === 'OPTIONS' || $method  === 'ANY') {
           
           // We check if body exist and if true we sanitize it to avoid XSS attacks.      
           if( file_get_contents("php://input") )
               $this->body = htmlspecialchars(file_get_contents("php://input"), ENT_QUOTES, 'UTF-8');
        }
    }

    //The method to extract the body in case of form post method.
    private function extractFormBody($method) {
        
        if ( $method === 'POST' ) {
           
            //We sanitize the form body if it exist to avoid XSS attacks.
            if (!empty($_POST)) {

                foreach ($_POST as $key => $value) {
                    $_POST[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
               $this->formBody = $_POST;
        }
    }
}