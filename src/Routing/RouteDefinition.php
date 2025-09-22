<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

class RouteDefinition {
  protected string $method;
  protected string $pattern;
  protected mixed $handler;
  protected array $params = [];

  public function __construct(string $method, string $pattern, callable $handler) {
    $this->method = strtoupper($method);
    $this->pattern = $this->normalizePattern($pattern);
    $this->handler = $handler;
  }

  public function getMethod(): string {
    return $this->method;
  }

  public function getPattern(): string {
    return $this->pattern;
  }

  public function getHandler(): callable {
    return $this->handler;
  }

  public function getParams(): array {
    return $this->params;
  }

  public function setParams(array $params): self {
    $this->params = $params;
    return $this;
  }

  public function matches(string $method, string $path): bool {
    if ($this->method !== strtoupper($method)) {
      return false;
    }

    $regex = $this->compilePattern();
    return (bool) preg_match($regex, $path);
  }

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

  protected function normalizePattern(string $pattern): string {
    $pattern = '/' . trim($pattern, '/');
    return $pattern === '/' ? '/' : rtrim($pattern, '/');
  }

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

  protected function extractParamNames(): array {
    preg_match_all('/\{([^}]+)\}/', $this->pattern, $matches);
    return array_map(function ($param) {
      return rtrim($param, '?');
    }, $matches[1]);
  }

  protected function isParamOptional(string $name): bool {
    return str_contains($this->pattern, '{' . $name . '?}');
  }
}
