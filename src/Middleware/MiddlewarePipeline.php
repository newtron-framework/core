<?php
declare(strict_types=1);

namespace Newtron\Core\Middleware;

use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;

class MiddlewarePipeline {
  private array $middleware = [];

  /**
   * Add a middleware to the pipeline
   *
   * @param  string $middleware The fully qualified class name of the middleware to add
   * @return MiddlewarePipeline The pipeline instance for chaining
   * @throws \InvalidArgumentException If the middleware class does not exist or does not implement MiddlewareInterface
   */
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

  /**
   * Process the request through the middleware pipeline
   *
   * @param  Request  $request The current request
   * @param  callable $handler The route handler
   * @return Response
   */
  public function process(Request $request, callable $handler): Response {
    $pipeline = array_reduce(
      array_reverse($this->middleware),
      fn($next, $middleware) => fn($request) => (new $middleware)->handle($request, $next),
      $handler,
    );

    return $pipeline($request);
  }
}
