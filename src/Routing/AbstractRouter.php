<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

use Newtron\Core\Application\App;
use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;
use Newtron\Core\Http\Status;
use Newtron\Core\Middleware\MiddlewarePipeline;

abstract class AbstractRouter {
  protected RouteCollection $routes;

  protected array $groupStack = [];
  protected ?string $currentGroupPrefix = null;

  public function __construct() {
    $this->routes = new RouteCollection();
    Route::setRouter($this);
  }

  /**
   * Load application routes
   */
  abstract public function loadRoutes(): void;

  /**
   * Get all loaded routes
   *
   * @return array Array of loaded route data
   */
  public function getRoutes(): array {
    return $this->routes->all();
  }

  /**
   * Register a GET route
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public function get(string $pattern, callable $handler): RouteDefinition {
    return $this->addRoute('GET', $pattern, $handler);
  }

  /**
   * Register a POST route
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public function post(string $pattern, callable $handler): RouteDefinition {
    return $this->addRoute('POST', $pattern, $handler);
  }

  /**
   * Register a PUT route
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public function put(string $pattern, callable $handler): RouteDefinition {
    return $this->addRoute('PUT', $pattern, $handler);
  }

  /**
   * Register a PATCH route
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public function patch(string $pattern, callable $handler): RouteDefinition {
    return $this->addRoute('PATCH', $pattern, $handler);
  }

  /**
   * Register a DELETE route
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public function delete(string $pattern, callable $handler): RouteDefinition {
    return $this->addRoute('DELETE', $pattern, $handler);
  }

  /**
   * Register a route that accepts any method
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public function any(string $pattern, callable $handler): array {
    $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    return $this->match($methods, $pattern, $handler);
  }

  /**
   * Register a route for the given methods
   *
   * @param  array    $methods The methods to allow
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public function match(array $methods, string $pattern, callable $handler): array {
    $routes = [];
    foreach ($methods as $method) {
      $routes[] = $this->addRoute($method, $pattern, $handler);
    }
    return $routes;
  }

  /**
   * Create a route group
   *
   * @param  array    $attributes Attributes for the route group
   * @param  callable $callback   The callback function for the route group
   */
  public function group(array $attributes, callable $callback): void {
    $this->groupStack[] = $attributes;

    $previousGroupPrefix = $this->currentGroupPrefix;

    if (isset($attributes['prefix'])) {
      $this->currentGroupPrefix = $previousGroupPrefix ?
        $previousGroupPrefix . '/' . trim($attributes['prefix'], '/') :
        trim($attributes['prefix'], '/');
    }

    call_user_func($callback, $this);

    array_pop($this->groupStack);
    $this->currentGroupPrefix = $previousGroupPrefix;
  }

  /**
   * Dispatch a routing request
   *
   * @param  Request $request The HTTP request
   * @return ?RouteDefinition The matching RouteDefinition if it exists, null otherwise
   */
  public function dispatch(Request $request): ?RouteDefinition {
    $this->loadRoutes();

    $method = $request->getMethod();
    $path = $request->getPath();

    $route = $this->routes->match($method, $path);

    return $route;
  }

  /**
   * Execute a route handler
   *
   * @param  RouteDefinition $route   The RouteDefinition to be executed
   * @param  Request         $request The HTTP request
   * @return Response The HTTP response
   * @throws \InvalidArgumentException If the handler is of an invalid type
   */
  public function execute(RouteDefinition $route, Request $request): Response {
    $finalHandler = function (Request $request) use ($route) {
      $handler = $route->getHandler();
      $params = $route->getParams();

      if (is_callable($handler) || is_string($handler)) {
        $result = App::getContainer()->call($handler, $params);
        return $this->normalizeResponse($result);
      }

      throw new \InvalidArgumentException('Invalid route handler type');
    };

    $pipeline = new MiddlewarePipeline();
    foreach (App::getGlobalMiddleware() as $middleware) {
      $pipeline->pipe($middleware);
    }
    foreach ($route->getMiddleware() as $middleware) {
      $pipeline->pipe($middleware);
    }

    return $pipeline->process($request, $finalHandler);
  }

  /**
   * Add a route
   *
   * @param  string   $method  The method for the route
   * @param  string   $pattern The route pattern
   * @param  callable $handler The handler for the route
   * @return RouteDefinition The RouteDefinition object for the route
   */
  protected function addRoute(string $method, string $pattern, callable $handler): RouteDefinition {
    if ($pattern !== '/') {
      $pattern = '/' . trim($pattern, '/');
    }

    if ($this->currentGroupPrefix) {
      $prefix = '/' . trim($this->currentGroupPrefix, '/');
      $pattern = $prefix . ($pattern === '/' ? '' : $pattern);
    }

    $route = new RouteDefinition($method, $pattern, $handler);

    $this->routes->add($route);

    return $route;
  }

  /**
   * Normalize the HTTP response from a route
   *
   * @param  mixed $result The route execution result
   * @return Response The normalized HTTP response
   * @throws \InvalidArgumentException If the handler returned an invalid response type
   */
  protected function normalizeResponse(mixed $result): Response {
    if ($result instanceof Response) {
      return $result;
    }

    if (is_array($result)) {
      return Response::createJson($result);
    }

    if (is_string($result) || is_numeric($result)) {
      return Response::create((string) $result);
    }

    if (is_null($result)) {
      return Response::create('', Status::NO_CONTENT);
    }

    if (method_exists($result, '__toString')) {
      return Response::create((string) $result);
    }

    throw new \InvalidArgumentException('Invalid response type returned from route handler');
  }
}
