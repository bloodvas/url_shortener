<?php

namespace App;

use App\Controllers\ApiController;
use App\Services\UrlService;
use App\Models\Url;
use App\Services\UrlShortener;

class Router
{
    private array $routes = [];

    public function __construct()
    {
        $this->routes = [
            'POST /api/shorten' => [ApiController::class, 'shorten'],
            'GET /{shortCode}' => [ApiController::class, 'redirect'],
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/');

        if ($uri === '') {
            $uri = '/';
        }

        // Try exact match first
        $routeKey = "$method $uri";
        if (isset($this->routes[$routeKey])) {
            $this->callRoute($this->routes[$routeKey]);
            return;
        }

        // Try pattern matching
        foreach ($this->routes as $route => $handler) {
            [$routeMethod, $routePath] = explode(' ', $route, 2);
            
            if ($routeMethod !== $method) {
                continue;
            }
            
            // Convert route pattern to regex
            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                $this->callRoute($handler, $matches);
                return;
            }
        }
        
        // No route found
        http_response_code(404);
        echo 'Route not found';
    }

    private function callRoute(array $handler, array $params = []): void
    {
        [$controllerClass, $method] = $handler;
        
        // Create dependencies
        $urlModel = new Url();
        $urlShortener = new UrlShortener();
        $urlService = new UrlService($urlModel, $urlShortener);
        
        $controller = new $controllerClass($urlService);
        
        if (!empty($params)) {
            call_user_func_array([$controller, $method], $params);
        } else {
            $controller->$method();
        }
    }
}
