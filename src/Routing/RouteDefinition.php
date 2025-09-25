<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

use Newtron\Core\Middleware\MiddlewareInterface;

class RouteDefinition {
  protected string $method;
  protected string $pattern;
  protected mixed $handler;
  protected array $params = [];
  protected array $middleware = [];

  /**
   * @param  $method  The route request method
   * @param  $pattern The route pattern
   * @param  $handler The route handler
   */
  public function __construct(string $method, string $pattern, callable $handler) {
    $this->method = strtoupper($method);
    $this->pattern = $this->normalizePattern($pattern);
    $this->handler = $handler;
  }

  /**
   * Get the route request method
   *
   * @return string The request method
   */
  public function getMethod(): string {
    return $this->method;
  }

  /**
   * Get the route pattern
   *
   * @return string The route pattern
   */
  public function getPattern(): string {
    return $this->pattern;
  }

  /**
   * Get the route handler
   *
   * @return callable The route handler
   */
  public function getHandler(): callable {
    return $this->handler;
  }

  /**
   * Get the parameters for the route
   *
   * @return array The route parameters
   */
  public function getParams(): array {
    return $this->params;
  }

  /**
   * Set the parameters for the route
   *
   * @param  array $params The route parameters
   * @return RouteDefinition The RouteDefinition object for chaining
   */
  public function setParams(array $params): self {
    $this->params = $params;
    return $this;
  }

  /**
   * Get middleware for the route
   *
   * @return array The middleware for the route
   */
  public function getMiddleware(): array {
    return $this->middleware;
  }

  /**
   * Use a middleware for the route
   *
   * @param  string $middleware The fully qualified class name of the middleware to add
   * @return RouteDefinition The RouteDefinition object for chaining
   */
  public function withMiddleware(string $middleware): self {
    if (!class_exists($middleware)) {
      throw new \InvalidArgumentException("Middleware '{$middleware}' not found");
    }
    if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
      throw new \InvalidArgumentException("Middleware '{$middleware}' must implement MiddlewareInterface");
    }
    $this->middleware[] = $middleware;
    return $this;
  }

  /**
   * Check whether the route matches the given request method and path
   *
   * @param  string $method The request method
   * @param  string $path   The request path
   * @return bool True if the route matches, false otherwise
   */
  public function matches(string $method, string $path): bool {
    if ($this->method !== strtoupper($method)) {
      return false;
    }

    $regex = $this->compilePattern();
    return (bool) preg_match($regex, $path);
  }

  /**
   * Extract the route parameters from a path
   *
   * @param  string $path The request path
   * @return array The extracted parameters as name => value
   */
  public function extractParams(string $path): array {
    $regex = $this->compilePattern();

    if (preg_match($regex, $path, $matches)) {
      array_shift($matches);

      $params = [];
      $paramNames = $this->extractParamNames();
      $matchIndex = 0;

      foreach ($paramNames as $name) {
        if ($this->isParamOptional($name)) {
          $value1 = $matches[$matchIndex] ?? '';
          $value2 = $matches[$matchIndex + 1] ?? '';

          $value = $value1 ?: $value2 ?: null;
          $params[$name] = $value;
          $matchIndex += 2;
        } else {
          $params[$name] = $matches[$matchIndex] ?? null;
          $matchIndex += 1;
        }
      }

      return $params;
    }

    return [];
  }

  /**
   * Normalize the route pattern
   *
   * @param  string $pattern The route pattern
   * @return string The normalized pattern
   */
  protected function normalizePattern(string $pattern): string {
    $pattern = '/' . trim($pattern, '/');
    return $pattern === '/' ? '/' : rtrim($pattern, '/');
  }

  /**
   * Compile regex for the route pattern
   *
   * @return string the compiled regex
   */
  protected function compilePattern(): string {
    $pattern = $this->pattern;

    $pattern = preg_replace_callback('/\{([^}]+)\}/', function ($matches) {
      $paramName = $matches[1];

      if (str_ends_with($paramName, '?')) {
        $paramName = rtrim($paramName, '?');
        return '([^/]+)?';
      }

      return '([^/]+)';
    }, $pattern);

    $pattern = preg_replace('/\/\(([^)]+)\)\?/', '(?:/($1)|/)?', $pattern);

    $pattern = str_replace('*', '.*', $pattern);

    return '#^' . $pattern . '$#';
  }

  /**
   * Extract the names of the route parameters
   *
   * @return array The route parameter names
   */
  protected function extractParamNames(): array {
    preg_match_all('/\{([^}]+)\}/', $this->pattern, $matches);
    return array_map(function ($param) {
      return rtrim($param, '?');
    }, $matches[1]);
  }

  /**
   * Check whether a route parameter is optional
   *
   * @param  string $name The parameter name
   * @return bool True if the parameter is optional, false otherwise
   */
  protected function isParamOptional(string $name): bool {
    return str_contains($this->pattern, '{' . $name . '?}');
  }
}
