<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

use Newtron\Core\Application\App;
use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;
use Newtron\Core\Http\Status;

class FileBasedRouter extends AbstractRouter {
  public function loadRoutes(): void {
    $this->scanDirectory(NEWTRON_ROUTES);
  }

  public function execute(RouteDefinition $route, Request $request): Response {
    $handler = $route->getHandler();
    $params = $route->getParams();

    if (is_callable($handler) || is_string($handler)) {
      $result = App::getContainer()->call($handler, ['params' => $params]);
      return $this->normalizeResponse($result);
    }

    throw new \InvalidArgumentException('Invalid route handler type');
  }

  protected function scanDirectory(string $directory, string $prefix = ''): void {
    if (!is_dir($directory)) {
      return;
    }

    $files = scandir(NEWTRON_ROUTES);

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

  protected function createFileRoutes(string $filePath, string $pattern): void {
    $routeClass = include $filePath;
    if (!$routeClass instanceof FileRoute) {
      return;
    }

    $handler = function (array $params) use ($routeClass) {
      $request = App::getRequest();
      $method = strtolower($request->getMethod());
      if (!method_exists($routeClass, $method)) {
        return Response::create('Method not allowed', Status::NOT_ALLOWED);
      }
      $data = $routeClass->$method(...$params);
      return $routeClass->render($data);
    };
    $routes = Route::any($pattern, $handler);
    foreach ($routes as $route) {
      $this->routes->add($route);
    }
  }
}
