<?php

/**
 * Class SMRouter
 */
class SMRouter
{
    const CALLBACK = "CALLBACK";
    const CONSTRUCTOR_PARAM = "CONSTRUCTOR_PARAM";

    public $routes = array();
    public $supportedHttpMethods = array(
        "GET",
        "POST",
    );

    public $patterns = array(
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*'
    );

    /**
     * Defines a route w/ callback and method
     * @param $method
     * @param array $params
     */
    public function route($method, array $params)
    {
        if (!in_array(strtoupper($method), $this->supportedHttpMethods)) {
            $this->invalidMethodHandler();
        } else {
            $uri = strpos($params[0], '/') === 0 ? $params[0] : '/' . $params[0];
            $callback = $params[1];
            $constructorParam = isset($params[2]) ? $params[2] : array();
            $uri = preg_replace('/\/+/', '/', $uri);
            $this->routes[mb_strtoupper($method)][$uri][self::CALLBACK] = $callback;
            $this->routes[mb_strtoupper($method)][$uri][self::CONSTRUCTOR_PARAM] = $constructorParam;
        }
    }

    /**
     * Runs the callback for the given request
     */
    public function dispatch()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        $foundRoute = false;
        if (!empty($this->routes[$requestMethod])) {
            // Check if route is defined without regex
            if (in_array($uri, array_keys($this->routes[$requestMethod]))) {
                $foundRoute = $this->routeWithoutRegexp();
            } else {
                $foundRoute = $this->routeWithRegexp();
            }
        }

        // Run the error callback if the route was not found
        if ($foundRoute == false) {
            $this->defaultRequestHandler();
        }
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    private function routeWithoutRegexp()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // If route is not an object
        $route = $this->routes[$requestMethod][$uri];

        if (!is_object($route[self::CALLBACK])) {
            // Grab all parts based on a / separator
            $parts = explode('/', $route[self::CALLBACK]);

            // Collect the last index of the array
            $last = end($parts);

            // Grab the controller name and method call
            $segments = explode('@', $last);

            // Instance controller
            $reflectionClass = new ReflectionClass($segments[0]);
            if ($reflectionClass->getConstructor()) {
                $instance = $reflectionClass->newInstanceArgs($route[self::CONSTRUCTOR_PARAM]);
            } else {
                $instance = $reflectionClass->newInstance();
            }
            $reflectionMethod = new ReflectionMethod($instance, $segments[1]);
            $reflectionMethod->invoke($instance);
        } else {
            // Call closure
            $reflectionFunction = new ReflectionFunction($route[self::CALLBACK]);
            $reflectionFunction->invoke();
        }

        return true;
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    private function routeWithRegexp()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        $searches = array_keys($this->patterns);
        $replaces = array_values($this->patterns);

        $foundRoute = false;
        // Check if defined with regex
        foreach ($this->routes[$requestMethod] as $routeUri => $routeParam) {
            if (strpos($routeUri, ':') !== false) {
                $routeUri = str_replace($searches, $replaces, $routeUri);
            }

            if (preg_match('#^' . $routeUri . '$#', $uri, $matched)) {
                $foundRoute = true;

                // Remove $matched[0] as [1] is the first parameter.
                array_shift($matched);

                if (!is_object($routeParam[self::CALLBACK])) {

                    // Grab all parts based on a / separator
                    $parts = explode('/', $routeParam[self::CALLBACK]);

                    // Collect the last index of the array
                    $last = end($parts);

                    // Grab the controller name and method call
                    $segments = explode('@', $last);

                    // Instance controller
                    $reflectionClass = new ReflectionClass($segments[0]);
                    if ($reflectionClass->getConstructor()) {
                        $instance = $reflectionClass->newInstanceArgs($routeParam[self::CONSTRUCTOR_PARAM]);
                    } else {
                        $instance = $reflectionClass->newInstance();
                    }
                    $reflectionMethod = new ReflectionMethod($instance, $segments[1]);
                    $reflectionMethod->invokeArgs($instance, $matched);
                } else {
                    $reflectionFunction = new ReflectionFunction($routeParam[self::CALLBACK]);
                    $reflectionFunction->invokeArgs($matched);
                }
            }
        }

        return $foundRoute;
    }

    private function invalidMethodHandler()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        header("{$requestMethod} 405 Method Not Allowed");
        echo "{$requestMethod} 405 Method Not Allowed";
    }

    private function defaultRequestHandler()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        header("{$requestMethod} 404 Not Found");
        echo "{$requestMethod} 404 Not Found";
    }
}