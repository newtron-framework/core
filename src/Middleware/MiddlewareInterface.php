<?php
declare(strict_types=1);

namespace Newtron\Core\Middleware;

use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;

interface MiddlewareInterface {
  public function handle(Request $request, callable $next): Response;
}
