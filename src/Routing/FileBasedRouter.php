<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

use Newtron\Core\Application\App;
use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;
use Newtron\Core\Http\Status;
use Newtron\Core\Middleware\MiddlewarePipeline;

class FileBasedRouter extends AbstractRouter {
  /**
   * Load file-based routes from route files
   */
  public function loadRoutes(): void {
    $this->scanDirectory(NEWTRON_ROUTES);
  }

  /**
   * Execute a route handler
   *
   * @param  RouteDefinition $route   The RouteDefinition to be executed
   * @param  Request         $request The HTTP request
   * @return Response The HTTP response
   * @throws \InvalidArgumentException If the handler is of an invalid type
   */
  public function execute(RouteDefinition $route, Request $request): Response {
    $finalHandler = function (Request $request) use ($route) {
      $handler = $route->getHandler();
      $params = $route->getParams();

      if (is_callable($handler) || is_string($handler)) {
        $result = App::getContainer()->call($handler, ['params' => $params]);
        return $this->normalizeResponse($result);
      }

      throw new \InvalidArgumentException('Invalid route handler type');
    };

    $pipeline = new MiddlewarePipeline();
    foreach (App::getGlobalMiddleware() as $middleware) {
      $pipeline->pipe($middleware);
    }
    foreach ($route->getMiddleware() as $middleware) {
      $pipeline->pipe($middleware);
    }

    return $pipeline->process($request, $finalHandler);
  }

  /**
   * Scan a directory for route files
   *
   * @param  string $directory The directory to scan
   * @param  string $prefix    The route prefix
   */
  protected function scanDirectory(string $directory, string $prefix = ''): void {
    if (!is_dir($directory)) {
      return;
    }

    $files = scandir($directory);

    foreach ($files as $file) {
      if ($file === '.' || $file === '..') {
        continue;
      }

      $filePath = $directory . '/' . $file;

      if (is_dir($filePath)) {
        $this->scanDirectory($filePath, $prefix . '/' . $file);
        continue;
      }

      $routePath = $this->generateRouteFromFile($file, $prefix);
      $this->createFileRoutes($filePath, $routePath);
    }
  }

  /**
   * Generate a route pattern from a route file
   *
   * @param  string $filename The route file
   * @param  string $prefix   The route prefix
   * @return string The generated route pattern
   */
  protected function generateRouteFromFile(string $filename, string $prefix): string {
    $name = pathinfo($filename, PATHINFO_FILENAME);

    if (str_ends_with($name, '_index')) {
      $name = substr($name, 0, -6);
    }

    if ($name === $prefix || empty($name)) {
      return $prefix ?: '/';
    }

    $name = preg_replace('/\[([^\]]+)\]/', '{$1}', $name);

    $name = preg_replace('/\{([^}]+)\?\}/', '{$1?}', $name);

    $name = preg_replace ('/[.]/', '/', $name);

    return $prefix . '/' . $name;
  }

  /**
   * Create route definitions for a route file
   *
   * @param  string $filePath The route file
   * @param  string $pattern  The route pattern
   */
  protected function createFileRoutes(string $filePath, string $pattern): void {
    $routeConfig = include $filePath;
    $routeClass = $routeConfig[0];
    $options = $routeConfig[1] ?? [];
    if (!$routeClass instanceof FileRoute) {
      return;
    }

    foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
      if (!method_exists($routeClass, $method)) {
        continue;
      }
      $handler = function (array $params) use ($routeClass, $method) {
        $request = App::getRequest();
        $data = $routeClass->$method(...$params);
        return $routeClass->render($data);
      };
      $route = Route::$method($pattern, $handler);
      if (isset($options['middleware']) && is_array($options['middleware'])) {
        foreach ($options['middleware'] as $middleware) {
          $route->withMiddleware($middleware);
        }
      }
    }
  }
}
