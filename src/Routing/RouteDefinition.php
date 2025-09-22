<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

class RouteDefinition {
  protected string $method;
  protected string $pattern;
  protected mixed $handler;

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

  public function matches(string $method, string $path): bool {
    if ($this->method !== strtoupper($method)) {
      return false;
    }

    $regex = $this->compilePattern();
    return (bool) preg_match($regex, $path);
  }

  protected function normalizePattern(string $pattern): string {
    $pattern = '/' . trim($pattern, '/');
    return $pattern === '/' ? '/' : rtrim($pattern, '/');
  }

  protected function compilePattern(): string {
    $pattern = $this->pattern;

    return '#^' . $pattern . '$#';
  }
}
