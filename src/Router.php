<?php

namespace PatrikMokry;

class Router
{
    /**
     * @var self $instance
     */
    private static $instance = null;

    /**
     * @var null $URL
     */
    public static $URL = null;

    /**
     * @var null $REQUEST
     */
    public static $REQUEST = null;

    /**
     * @var array $params
     */
    public static $params = [];

    /**
     * @var array $queryParams
     */
    private static $queryParams = [];

    /**
     * This static property holds all routes
     *
     * @var array $routes
     */
    private static $routes = [];

    /**
     * Return current route
     *
     * @var string
     */
    public static $route = "";

    /**
     * Last route added
     *
     * @var string
     */
    private static $last = null;

    /**
     * @var string
     */
    private static $prefix = null;

    /**
     * @var string
     */
    private static $name = null;

    /**
     * @var array $doNotIncludeInParams
     */
    private static $doNotIncludeInParams = [];

    /**
     * @var array
     */
    private static $shortcuts = [
        'i' => '(\d+)',
        's' => '(\w+)',
        'locale' => '(sk|en)->sk'
    ];

    /**
     * Init new self instance
     */
    public static function init() {
        self::$instance = new self();
    }

    /**
     * Asign name to route
     *
     * @param $name
     */
    public function name($name)
    {
        $route = self::$routes[self::$last];
        unset(self::$routes[self::$last]);
        self::$routes[self::$name . $name] = $route;
    }

    /**
     * Add custom shortcut
     * shortcut with default value -> $regex = (val1|val2)->val1;
     *
     * @param $shortcut
     * @param $regex
     */
    public static function addShortcut($shortcut, $regex)
    {
        self::$shortcuts[$shortcut] = $regex;
    }

    /**
     * Get link from route name and params
     *
     * @param $route
     * @param array $params
     * @param $absolute
     * @return null
     */
    public static function link($route, $params = [], $absolute = true)
    {
        if (!isset(self::$routes[$route])) return null;
        $route = self::$routes[$route];

        $link = "";
        foreach ($route['params'] as $key => $param) {
            if (isset($param['real'])) {
                $link .= $param['pattern'] . '/';
            } else if (isset($params[$param['name']])) {
                // Chcek if param has default
                if (isset($param['default']) && $param['default'] !== $params[$param['name']]) {
                    $link .= $params[$param['name']] . '/';
                }
            }
        }

        // Add absolute path
        if ($absolute) {
            $link = self::$URL . $link;
        }

        // Cut slash at the end
        return rtrim($link, '/');
    }

    /**
     * Redirict to specific route or defined location
     *
     * @param $route
     * @param array $params
     */
    public static function redirect($route, $params = [])
    {
        if (isset(self::$routes[$route])) {
            $route = self::link($route, $params);
        }
        header('Location: ' . $route, true);
        die();
    }

    /**
     * Create prefixed routes
     *
     * @param $prefix
     * @param $callback
     * @param string $name
     * @param array $doNotIncludeInParams
     */
    public static function prefix($prefix, $callback, $name = '', $doNotIncludeInParams = [])
    {
        self::$prefix = $prefix;
        self::$name = $name;
        self::$doNotIncludeInParams = $doNotIncludeInParams;
        call_user_func($callback);
        self::$prefix = null;
        self::$name = null;
        self::$doNotIncludeInParams = [];
    }

    /**
     * Create route
     *
     * @param $route
     * @param $action
     * @param array $method
     * @return Router
     */
    public static function route($route, $action, $method = ["POST", "GET"])
    {
        $prefix = self::$prefix;
        if (empty($route) && !empty(self::$prefix)) {
            $prefix = rtrim(self::$prefix, '/');
        }


        $explodedRoute = explode("/", $prefix . $route);
        $params = [];
        $pattern = "";

        // create route
        foreach ($explodedRoute as $key => $r) {
            if (strpos($r, '}?') !== false) {
                $r = self::dynamic($r, $params, true);
                $pattern = substr($pattern, 0, -1);
                $dyn = true;
            } else if (strpos($r, '}') !== false) {
                $r = self::dynamic($r, $params, false);
                $pattern = substr($pattern, 0, -1);
                $dyn = true;
            } else {
                $params[] = [
                    'pattern' => $r,
                    'real' => false
                ];
            }
            $pattern .= ($key == 0 && !isset($dyn) ? '/' : '') . $r . '/';
        }

        // Create pattern
        $pattern = substr($pattern, 0, -1) . '/?';
        $pattern = '~^' . str_replace('/', '\/', $pattern) . '$~';

        // Save data to static property
        $name = uniqid();
        self::$routes[$name] = [
            'route' => $route,
            'pattern' => $pattern,
            'params' => $params,
            'action' => $action,
            'method' => $method,
            'doNotIncludeInParams' => self::$doNotIncludeInParams
        ];
        // Set last added
        self::$last = $name;

        // return self instance
        if (self::$instance == null) {
            self::init();
        }
        return self::$instance;
    }

    /**
     * Helper for route with GET method
     *
     * @param $route
     * @param $action
     * @return self
     */
    public static function get($route, $action)
    {
        return self::route($route, $action, ['GET']);
    }

    /**
     * Helper for route with POST method
     *
     * @param $route
     * @param $action
     * @return self
     */
    public static function post($route, $action)
    {
        return self::route($route, $action, ['POST']);
    }

    /**
     * Helper for route with PUT method
     *
     * @param $route
     * @param $action
     * @return self
     */
    public static function put($route, $action)
    {
        return self::route($route, $action, ['PUT']);
    }

    /**
     * Helper for route with DELETE method
     *
     * @param $route
     * @param $action
     * @return self
     */
    public static function delete($route, $action)
    {
        return self::route($route, $action, ['DELETE']);
    }

    /**
     * Helper for route with GET, POST, PUT, DELETE method
     *
     * @param $route
     * @param $action
     * @return self
     */
    public static function any($route, $action)
    {
        return self::route($route, $action, ['GET', 'POST', 'PUT', 'DELETE']);
    }

    /**
     * Create all routes for resource (CRUD)
     *
     * @param $route
     * @param $controller
     * @param string $name
     * @param string $shortcut
     */
    public static function resource($route, $controller, $name = null, $shortcut = 's')
    {
        self::$name = self::$name . $name;

        self::get($route . '/{page::paginator}?', $controller . "@index")->name('index');
        self::get($route . "/create", $controller . "@create")->name('create');
        self::post($route, $controller . "@store")->name('store');
        self::get($route . "/{id::" . $shortcut . "}", $controller . "@show")->name('show');
        self::get($route . "/{id::" . $shortcut . "}/edit", $controller . "@edit")->name('edit');
        self::put($route . "/{id::" . $shortcut . "}", $controller . "@update")->name('update');
        self::delete($route . "/{id::" . $shortcut . "}", $controller . "@destroy")->name('destroy');
        self::get($route . "/{id::" . $shortcut . "}/delete", $controller . "@destroy")->name('destroy_');

        self::$name = str_replace($name, '', self::$name);
    }


    /**
     * Handle dynamic parameter in route
     *
     * @param string $route
     * @param array $params
     * @param boolean $optional
     * @return mixed
     */
    private static function dynamic($route, &$params, $optional = false)
    {
        $shortcut = self::rules($route);

        $name = str_replace('{', '', $route);
        if (!$optional) $name = str_replace('}', '', $name);
        else $name = str_replace('}?', '', $name);

        if (strpos($name, '::')) {
            $name = substr($name, 0, strpos($name, "::"));
        }

        if (array_search($name, array_column($params, 'name')) === false) {

            $pattern = str_replace('(', '(/', $shortcut['shortcut']);
            $pattern = str_replace('|', '|/', $pattern);

            // If is optional add ? at the end of pattern
            if ($optional) {
                $pattern .= '?';
            }

            $params[] = [
                'name' => $name,
                'pattern' => $pattern,
                'default' => $shortcut['default']
            ];
        } else {
            die('Parameter with name ' . $name . ' has been already defined');
        }
        return $pattern;
    }

    /**
     * Dynamic route rules
     *
     * @param $route
     * @return mixed
     */
    private static function rules($route)
    {
        if (preg_match('~::(.*?)}~', $route, $match)) {
            list(, $shortcut) = $match;
            if (isset(self::$shortcuts[$shortcut])) {
                // Try to get default value from shortcut
                $default = '';
                $shortcut = self::$shortcuts[$shortcut];
                if (preg_match('~->(.*?)$~', $shortcut, $match)) {
                    $default = $match[1];
                    $shortcut = str_replace($match[0], '', $shortcut);
                }
                // Return shortcut and default value
                return [
                    'shortcut' => $shortcut,
                    'default' => $default
                ];
            }
        }
        return [
            'shortcut' => self::$shortcuts['s'],
            'default' => ''
        ];
    }

    /**
     * Execute router
     *
     * @param $ROOT
     * @return boolean
     */
    public static function execute($ROOT)
    {
        // Generate request and absolute path
        self::generateURL($ROOT);

        // Get query string
        $request = explode('?', $_SERVER['REQUEST_URI']);
        $queryParams = [];
        if (isset($request[1])) {
            $queryParams = $request[1];
            parse_str($queryParams, $queryParams);
            self::$queryParams = $queryParams;
        }

        // Modify request
        $request = '/' . trim(self::$REQUEST, '/');

        // Find route
        foreach (self::$routes as $routeKey => $route) {
            $matchMethod = in_array($_SERVER['REQUEST_METHOD'], $route['method']) || (isset($_POST["_method"])
                    && in_array($_POST["_method"], $route['method']));
            if (preg_match($route['pattern'], $request, $match) && $matchMethod) {

                // Default variables
                $explodedRequest = explode('/', ltrim($request, '/'));
                $routeParams = $route['params'];
                $params = [];

                // Match request params with params in array - static params
                foreach ($explodedRequest as $key => $value) {
                    foreach ($routeParams as $k => $routeParam) {
                        // Go to the next request part
                        if (isset($routeParam['real']) && $routeParam['pattern'] == $value) {
                            unset($routeParams[$k]);
                            unset($explodedRequest[$key]);
                            break;
                        }
                    }
                }

                // Match request params with params in array - dynamic params
                foreach ($explodedRequest as $key => $value) {
                    foreach ($routeParams as $k => $routeParam) {
                        if ($k >= $key && ($k - $key) < 2) {
                            if (preg_match('~' . $routeParam['pattern'] . '~', '/' . $value, $match)) {
                                $params[$routeParam['name']] = $value;
                                unset($routeParams[$k]);
                            }
                        }
                    }
                }

                // Last try to assign params - only with default values
                foreach ($routeParams as $k => $routeParam) {
                    if (!isset($routeParam['default'])) continue;
                    $params[$routeParam['name']] = $routeParam['default'];
                }

                // Resort params to default order
                $resortedParams = [];
                foreach ($route['params'] as $k => $routeParam) {
                    if (isset($routeParam['name']) && isset($params[$routeParam['name']])) {
                        $resortedParams[$routeParam['name']] = $params[$routeParam['name']];
                    }
                }

                // Merge with query params
                $resortedParams = array_merge($resortedParams, $queryParams);
                self::$params = $resortedParams;

                // Check if can redirect to some defaults
                $link =  self::link($routeKey, $resortedParams, false);
                if (trim($request, '/') !== $link) {
                    header('Location: ' . self::$URL . $link . (!empty(self::$queryParams) ? '?' . http_build_query(self::$queryParams) : ''));
                }

                // Setup default route and url
                self::$route = $route;

                // If is post request
                if (in_array('POST', $route['method']) || in_array('PUT', $route['method']) || in_array('DELETE', $route['method'])) {
                    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
                    if (strcasecmp($contentType, 'application/json') !== false) {
                        $post = json_decode(file_get_contents('php://input'), true);
                    } else {
                        $post = $_POST;
                    }
                    array_unshift($resortedParams, $post);
                }

                // Check if there are some unwanted params
                $params = $resortedParams;
                foreach ($resortedParams as $key => $value) {
                    if (in_array($key, $route['doNotIncludeInParams']) && !is_numeric($key)) {
                        unset($resortedParams[$key]);
                    }
                }

                // Call action
                if (is_callable($route['action'])) {
                    call_user_func_array($route['action'], $resortedParams);
                } else if (strpos($route['action'], '@') !== false) {
                    // call controller
                    list($controller, $method) = explode('@', $route['action']);

                    // init new controller
                    $controller = new $controller;

                    // Check if class has parent
                    $parentControllers = class_parents($controller);
                    if (!empty($parentControllers)) {
                        end($parentControllers);
                        $parentController = $parentControllers[key($parentControllers)];
                        $parentController = new $parentController;

                        // Add properties to parent class
                        foreach ($params as $key => $value) {
                            $parentController::$params[$key] = $value;
                        }
                    }

                    // Call method
                    call_user_func_array([$controller, $method], $resortedParams);
                }
                return 'SUCCESS';
            }
        }
        return 'PAGE_NOT_FOUND';
    }

    /**
     * Generate URL
     *
     * @param $ROOT
     */
    private static function generateURL($ROOT)
    {
        $baseLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $baseLink .= "://" . $_SERVER['SERVER_NAME'];
        $baseLink .= ($_SERVER['SERVER_PORT'] !== '80' ? ':' . $_SERVER['SERVER_PORT'] : '');

        $baseRequest = '';

        $request = $_SERVER['REQUEST_URI'];
        foreach (explode('/', $ROOT) as $key => $value) {
            if ($value == '') continue;
            if (preg_match('~/' . $value . '~', $request)) {
                $baseRequest .= $value . '/';
            }
            $request = preg_replace('~/' . $value . '~', '', $request, 1);
        }

        self::$URL = $baseLink . '/' . $baseRequest;
        self::$REQUEST = explode('?', $request)[0];
    }
}