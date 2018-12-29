<?php

namespace PatrikMokry\Router;

class Router
{

    /**
     * @var self $instance
     */
    private static $instance = null;

    /**
     * This static property holds all routes
     *
     * @var array $routes
     */
    private static $routes = [];

    /**
     * Last route added
     *
     * @var string
     */
    private static $last = "";

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
        self::$routes[$name] = $route;
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
     * Create route
     *
     * @param $route
     * @param $action
     * @param array $method
     * @return Router
     */
    public static function route($route, $action, $method = ["POST", "GET"])
    {
        $explodedRoute = explode("/", $route);
        $params = [];
        $pattern = "";

        // create route
        foreach ($explodedRoute as $key => $r) {
            if (strpos($r, '}?') !== false) {
                $r = self::dynamicOptional($r, $params, $key);
                $pattern = substr($pattern, 0, -1);
                $optional = true;
            } else if (strpos($r, '}') !== false) {
                $r = self::dynamic($r, $params, $key);
                $pattern = substr($pattern, 0, -1);
            } else {
                $params[] = [
                    'pattern' => $r,
                    'real' => false
                ];
            }
            $pattern .= ($key == 0 && !isset($optional) ? '/' : '') . $r . '/';
        }

        // Create pattern
        $pattern = substr($pattern, 0, -1) . '/?';
        $pattern = '~^' . str_replace('/', '\/', $pattern) . '$~';

        // Save data to static property
        self::$routes[$pattern] = [
            'pattern' => $pattern,
            'params' => $params,
            'action' => $action,
            'method' => $method
        ];
        // Set last added
        self::$last = $pattern;

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
     */
    public static function get($route, $action)
    {
        self::route($route, $action, ['GET']);
    }

    /**
     * Helper for route with POST method
     *
     * @param $route
     * @param $action
     */
    public static function post($route, $action)
    {
        self::route($route, $action, ['POST']);
    }

    /**
     * Helper for route with PUT method
     *
     * @param $route
     * @param $action
     */
    public static function put($route, $action)
    {
        self::route($route, $action, ['PUT']);
    }

    /**
     * Helper for route with DELETE method
     *
     * @param $route
     * @param $action
     */
    public static function delete($route, $action)
    {
        self::route($route, $action, ['DELETE']);
    }

    /**
     * Helper for route with GET, POST, PUT, DELETE method
     *
     * @param $route
     * @param $action
     */
    public static function any($route, $action)
    {
        self::route($route, $action, ['GET', 'POST', 'PUT', 'DELETE']);
    }

    /**
     * Create all routes for resource (CRUD)
     *
     * @param $route
     * @param $controller
     * @param string $shortcut
     */
    public static function resource($route, $controller, $shortcut = 's')
    {
        self::get($route, $controller . "@index");
        self::get($route . "/create", $controller . "@create");
        self::post($route, $controller . "@store");
        self::get($route . "/{id::" . $shortcut . "}", $controller . "@show");
        self::get($route . "/{id::" . $shortcut . "}/edit", $controller . "@edit");
        self::put($route . "/{id::" . $shortcut . "}", $controller . "@update");
        self::delete($route . "/{id::" . $shortcut . "}", $controller . "@destroy");
    }


    /**
     * Handle dynamic parameter in route
     *
     * @param $route
     * @param $params
     * @param $key
     * @return mixed
     */
    private static function dynamic($route, &$params, $key)
    {
        $shortcut = self::rules($route);

        $name = str_replace('{', '', $route);
        $name = str_replace('}', '', $name);
        if (strpos($name, '::')) {
            $name = substr($name, 0, strpos($name, "::"));
        }

        if (!isset($params[$name])) {
            $pattern = str_replace('(', '(/', $shortcut['shortcut']);
            $pattern = str_replace('|', '|/', $pattern) . '?';
            $params[] = [
                'name' => $name,
                'pattern' => $pattern,
                'default' => $shortcut['default']
            ];
        } else {
            die('Parameter ' . $name . ' already exist');
        }
        return $pattern;
    }

    /**
     * Handle dynamic parameter in route
     *
     * @param $route
     * @param $params
     * @param $key
     * @return mixed
     */
    private static function dynamicOptional($route, &$params, $key)
    {
        $shortcut = self::rules($route);

        $name = str_replace('{', '', $route);
        $name = str_replace('}?', '', $name);
        if (strpos($name, '::')) {
            $name = substr($name, 0, strpos($name, "::"));
        }

        if (!isset($params[$name])) {
            $pattern = str_replace('(', '(/', $shortcut['shortcut']) . '?';
            $pattern = str_replace('|', '|/', $pattern) . '?';
            $params[] = [
                'name' => $name,
                'pattern' => $pattern,
                'optional' => true,
                'default' => $shortcut['default']
            ];
        } else {
            die('Parameter ' . $name . ' already exist');
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
                $default = null;
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
            'default' => null
        ];
    }

    /**
     * Execute router
     *
     * @param $request
     */
    public static function execute($request)
    {
        $request = rtrim($request, '/');
        foreach (self::$routes as $route) {
            $matchMethod = in_array($_SERVER['REQUEST_METHOD'], $route['method']) || (isset($_POST["_method"]) && in_array($_POST["_method"], $route['method']));
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

                // Call action
                if (is_callable($route['action'])) {
                    call_user_func_array($route['action'], $params);
                }
            }
        }
    }
}