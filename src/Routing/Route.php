<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

class Route {
  protected static ?AbstractRouter $router = null;

  /**
   * Set the router for the route helper
   *
   * @param  AbstractRouter $router The router to use
   */
  public static function setRouter(AbstractRouter $router): void {
    static::$router = $router;
  }

  /**
   * Register a GET route
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public static function get(string $pattern, callable $handler): RouteDefinition {
    return static::$router->get($pattern, $handler);
  }

  /**
   * Register a POST route
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public static function post(string $pattern, callable $handler): RouteDefinition {
    return static::$router->post($pattern, $handler);
  }

  /**
   * Register a PUT route
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public static function put(string $pattern, callable $handler): RouteDefinition {
    return static::$router->put($pattern, $handler);
  }

  /**
   * Register a PATCH route
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public static function patch(string $pattern, callable $handler): RouteDefinition {
    return static::$router->patch($pattern, $handler);
  }

  /**
   * Register a DELETE route
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public static function delete(string $pattern, callable $handler): RouteDefinition {
    return static::$router->delete($pattern, $handler);
  }

  /**
   * Register a route that accepts any method
   *
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public static function any(string $pattern, callable $handler): array {
    return static::$router->any($pattern, $handler);
  }

  /**
   * Register a route for the given methods
   *
   * @param  array    $methods The methods to allow
   * @param  string   $pattern The route pattern
   * @param  callable $handler The route handler
   * @return RouteDefinition The created route
   */
  public static function match(array $methods, string $pattern, callable $handler): array {
    return static::$router->match($methods, $pattern, $handler);
  }

  /**
   * Create a route group
   *
   * @param  array    $attributes Attributes for the route group
   * @param  callable $callback   The callback function for the route group
   */
  public static function group(array $attributes, callable $callback): void {
    static::$router->group($attributes, $callback);
  }
}
