<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

class Route {
  protected static ?AbstractRouter $router = null;

  public static function setRouter(AbstractRouter $router): void {
    static::$router = $router;
  }

  public static function get(string $pattern, callable $handler): RouteDefinition {
    return static::$router->get($pattern, $handler);
  }

  public static function post(string $pattern, callable $handler): RouteDefinition {
    return static::$router->post($pattern, $handler);
  }

  public static function put(string $pattern, callable $handler): RouteDefinition {
    return static::$router->put($pattern, $handler);
  }

  public static function patch(string $pattern, callable $handler): RouteDefinition {
    return static::$router->patch($pattern, $handler);
  }

  public static function delete(string $pattern, callable $handler): RouteDefinition {
    return static::$router->delete($pattern, $handler);
  }

  public static function any(string $pattern, callable $handler): array {
    return static::$router->any($pattern, $handler);
  }

  public static function match(array $methods, string $pattern, callable $handler): array {
    return static::$router->match($methods, $pattern, $handler);
  }

  public static function group(array $attributes, callable $callback): void {
    static::$router->group($attributes, $callback);
  }
}
