<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

/**
 * Base class for a file-based route.
 * Allow a request method by defining a handler function with a matching name (e.g. define get() to allow GET requests).
 * The result returned from the handler function will be passed to render() as $data.
 * Return a route data array at the end of the file. Minimal example:
 * ```
 * return [ new MyRoute() ];
 * ```
 */
abstract class FileRoute {
  abstract public function render(mixed $data): mixed;
}
