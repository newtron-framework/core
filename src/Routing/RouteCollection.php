<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

class RouteCollection {
  protected array $routes = [];

  /**
   * Add a route to the collection
   *
   * @param  RouteDefinition $route The route to add
   */
  public function add(RouteDefinition $route): void {
    $this->routes[] = $route;
  }

  /**
   * Find a matching route for a request method and path
   *
   * @param  string $method The request method
   * @param  string $path   The request path
   * @return ?RouteDefinition The matching RouteDefinition if it exists, null otherwise
   */
  public function match(string $method, string $path): ?RouteDefinition {
    /** @var RouteDefinition $route */
    foreach ($this->routes as $route) {
      if ($route->matches($method, $path)) {
        $params = $route->extractParams($path);
        $route->setParams($params);
        return $route;
      }
    }

    return null;
  }

  /**
   * Get all routes in the collection
   *
   * @return array Array of routes in the collection
   */
  public function all(): array {
    return $this->routes;
  }
}
