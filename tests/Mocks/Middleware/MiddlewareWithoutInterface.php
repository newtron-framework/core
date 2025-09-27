<?php
declare(strict_types=1);

namespace Tests\Mocks\Middleware;

use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;

class MiddlewareWithoutInterface {
  public function handle(Request $request, callable $next): Response {
    $next($request);
  }
}
