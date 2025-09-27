<?php
declare(strict_types=1);

namespace Newtron\Core\Middleware;

use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;

interface MiddlewareInterface {
  /**
   * Process a request
   *
   * @param  Request  $request The current request
   * @param  callable $next    The next middlware in the pipeline
   * @return Response
   */
  public function handle(Request $request, callable $next): Response;
}
