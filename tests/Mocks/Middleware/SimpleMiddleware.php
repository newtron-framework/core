<?php
declare(strict_types=1);

namespace Tests\Mocks\Middleware;

use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;
use Newtron\Core\Middleware\MiddlewareInterface;

class SimpleMiddleware implements MiddlewareInterface {
  public function handle(Request $request, callable $next): Response {
    return Response::create('middleware_called');
  }
}
