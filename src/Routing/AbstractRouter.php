<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

use Newtron\Core\Application\App;
use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;
use Newtron\Core\Http\Status;

abstract class AbstractRouter {
  protected RouteCollection $routes;

  protected array $groupStack = [];
  protected ?string $currentGroupPrefix = null;

  public function __construct() {
    $this->routes = new RouteCollection();
    Route::setRouter($this);
  }

  abstract public function loadRoutes(): void;

  public function getRoutes(): array {
    return $this->routes->all();
  }

  public function get(string $pattern, callable $handler): RouteDefinition {
    return $this->addRoute('GET', $pattern, $handler);
  }

  public function post(string $pattern, callable $handler): RouteDefinition {
    return $this->addRoute('POST', $pattern, $handler);
  }

  public function put(string $pattern, callable $handler): RouteDefinition {
    return $this->addRoute('PUT', $pattern, $handler);
  }

  public function patch(string $pattern, callable $handler): RouteDefinition {
    return $this->addRoute('PATCH', $pattern, $handler);
  }

  public function delete(string $pattern, callable $handler): RouteDefinition {
    return $this->addRoute('DELETE', $pattern, $handler);
  }

  public function any(string $pattern, callable $handler): array {
    $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    return $this->match($methods, $pattern, $handler);
  }

  public function match(array $methods, string $pattern, callable $handler): array {
    $routes = [];
    foreach ($methods as $method) {
      $routes[] = $this->addRoute($method, $pattern, $handler);
    }
    return $routes;
  }

  public function group(array $attributes, callable $callback): void {
    $this->groupStack[] = $attributes;

    $previousGroupPrefix = $this->currentGroupPrefix;

    if (isset($attributes['prefix'])) {
      $this->currentGroupPrefix = $previousGroupPrefix ?
        $previousGroupPrefix . '/' . trim($attributes['prefix'], '/') :
        trim($attributes['prefix'], '/');
    }

    call_user_func($callback);

    array_pop($this->groupStack);
    $this->currentGroupPrefix = $previousGroupPrefix;
  }

  public function dispatch(Request $request): ?RouteDefinition {
    $this->loadRoutes();

    $method = $request->getMethod();
    $path = $request->getPath();

    $route = $this->routes->match($method, $path);

    return $route;
  }

  public function execute(RouteDefinition $route, Request $request): Response {
    $handler = $route->getHandler();
    $params = $route->getParams();

    if (is_callable($handler) || is_string($handler)) {
      $result = App::getContainer()->call($handler, $params);
      return $this->normalizeResponse($result);
    }

    throw new \InvalidArgumentException('Invalid route handler type');
  }

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
