<?php
declare(strict_types=1);

namespace Newtron\Core\Middleware;

use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;

class MiddlewarePipeline {
  private array $middleware = [];

  public function pipe(string $middleware): self {
    if (!class_exists($middleware)) {
      throw new \InvalidArgumentException("Middleware '{$middleware}' not found");
    }
    if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
      throw new \InvalidArgumentException("Middleware '{$middleware}' must implement MiddlewareInterface");
    }
    $this->middleware[] = $middleware;
    return $this;
  }

  public function process(Request $request, callable $handler): Response {
    $pipeline = array_reduce(
      array_reverse($this->middleware),
      fn($next, $middleware) => fn($request) => (new $middleware)->handle($request, $next),
      $handler,
    );

    return $pipeline($request);
  }
}
