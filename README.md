
# PHP Router - Fast router for PHP  
[![Total Downloads](https://poser.pugx.org/patrikmokry/router/downloads)](https://packagist.org/packages/patrikmokry/router) [![License](https://poser.pugx.org/patrikmokry/router/license)](https://packagist.org/packages/patrikmokry/router) [![Latest Version](https://poser.pugx.org/patrikmokry/router/v/unstable)](https://packagist.org/packages/patrikmokry/router)

A lightweight and simple object oriented PHP Router with support of Controllers.
  
## This library provides a fast implementation of a regular expression based router.  
  
 * [Defining routes](#defining-routes)  
 * [Regex Shortcuts](#regex-shortcuts)  
 * [Named Routes for Reverse Routing ](#named-routes-for-reverse-routing)  
 * [Prefix Groups](#prefix-groups) 
  
## Easy to install with composer  

Install via composer  

```  
composer require patrikmokry/router  
```  
  
Usage  
-----  
### Example  
  
~~~PHP
// index.php

use PatrikMokry\Router;  

require_once __DIR__ . '/vendor/autoload.php';  

Router::get('example', function() {  
    return 'This route responds to requests with the GET method at the path /example';  
});  
Router::get('example/{id}', function($id) {  
    return 'This route responds to requests with the GET method at the path /example/<anything>';  
});  
Router::get('example/{id}?', function() {  
    return 'This route responds to requests with the GET method at the path /example/[optional]';  
});  
Router::post('example', function() {  
    return 'This route responds to requests with the POST method at the path /example';  
});  
  
Router::execute($_SERVER['REQUEST_URI'], 'http://example.com/');  
~~~  
~~~PHP
// .htaccess

RewriteEngine on  
  
RewriteCond %{REQUEST_FILENAME} !-f  
RewriteCond %{REQUEST_FILENAME} !-d  
RewriteCond %{REQUEST_FILENAME} !-l  
  
RewriteRule ^(.+)$ index.php [QSA,L]
~~~
  
### Defining Routes  
  
~~~PHP  
use PatrikMokry\Router;  
  
Router::get($route, $action);  
Router::post($route, $action);  
Router::put($route, $action);  
Router::delete($route, $action);  
Router::any($route, $action);  
~~~  
  
 > These helper methods are wrappers around `route($route, $action, $method = ["POST", "GET"])`  
 > Neither `$_PUT` nor `$_DELETE` does not exist so in your request you must define field with name `_method`

### Regex Shortcuts  
  
```  
:i => (\d+)   # numbers only  
:s => (\w+)   # any word, character  
  
use in routes:  
  
'/user/{name::i}'  
'/user/{name::s}'  
```  
  
##### Custom shortcuts  
  
~~~PHP  
Router::addShortcut(name, regex)  
  
// create shortcut with default value
Router::addShortcut('locale', '(sk|en)->sk)')  
~~~  
  
  
### Named Routes for Reverse Routing 
  
~~~PHP  
Router::get('example/{id}', function() {
	return 'example';
})->name('example');

echo Router::link('example', ['id' => 48]) // example/48
~~~  
  
### Prefix Groups  
  
~~~PHP 
// If you need some prefix e.g. admin, api, locale, ...

Router::prefix('admin/', function() {
	Router::get('example/{id}', function() {
		return 'example';
	});
});
~~~  


### Contributing

1. Fork it ( https://github.com/MokryPatrik/PHP-Router/fork )
2. Create your feature branch (git checkout -b my-new-feature)
3. Commit your changes (git commit -am 'Add some feature')
4. Push to the branch (git push origin my-new-feature)
5. Create a new Pull Request
