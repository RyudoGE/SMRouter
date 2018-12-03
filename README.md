SMRouter  
=====

inpired by https://github.com/noahbuscher/macaw
 
and 

https://medium.com/the-andela-way/how-to-build-a-basic-server-side-routing-system-in-php-e52e613cf241

SMRouter is a simple router working on PHP 5.2+.

### Install

```
```

### Examples

```PHP
$url = $_SERVER['SCRIPT_NAME'];
```

```PHP
$router = new SMRouter();
$router->route(
    "get",
    array(
        $url . '/test/',
        'TestController@viewPlus'
    )
);
```
```PHP
$router->route(
    "get",
    array(
        $url . '/test/(:num)',
        'TestController@viewNumber'
    )
);
```
```PHP
$router->route(
    "get",
    array(
        $url . '/test/(:all)/(:num)',
        'TestContrl@viewWord',
        array(
            new Smarty()
        )
    )
);
```

### List of parameters

```PHP
$router->route(
    $method,
    array(
        $uri,
        $function,
        array(
            $param1
            ...
            $paramN
        )
    )
);
```

```
$method - http method GET or POST

$uri - alias of path

$function - NameOfClass@method OR callback function

$param1 ... $paramN - some params for constructor of NameOfClass
```
