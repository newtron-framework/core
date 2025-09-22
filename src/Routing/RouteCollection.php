<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

class RouteCollection {
  protected array $routes = [];

  public function add(RouteDefinition $route): void {
    $this->routes[] = $route;
  }

  public function match(string $method, string $path): ?RouteDefinition {
    /** @var RouteDefinition $route */
    foreach ($this->routes as $route) {
      if ($route->matches($method, $path)) {
        return $route;
      }
    }

    return null;
  }

  public function all(): array {
    return $this->routes;
  }
}
