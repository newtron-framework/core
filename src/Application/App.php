<?php
declare(strict_types=1);

namespace Newtron\Core\Application;

use Newtron\Core\Container\Container;
use Newtron\Core\Container\ServiceProvider;
use Newtron\Core\Container\ServiceProviderRegistry;
use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;

class App {
  private static $instance;
  private static Container $container;
  private static ServiceProviderRegistry $serviceProviderRegistry;

  private function __construct(string $rootPath) {
    $this->loadEnv($rootPath);

    $this->defineConstants($rootPath);

    static::$container = new Container();
    static::$serviceProviderRegistry = new ServiceProviderRegistry(static::$container);
    $this->registerServices();
  }

  public static function create(string $root): self {
    if (!isset(self::$instance)) {
      self::$instance = new self($root);
    }

    return self::$instance;
  }

  public static function addServiceProvider(ServiceProvider $provider): void {
    self::$serviceProviderRegistry->register($provider);
  }

  public static function run(): void {
    self::$serviceProviderRegistry->boot();

    $request = new Request();
    self::$container->instance(Request::class, $request);

    Response::create("Path requested: " . $request->getPath())->send();
  }

  public static function getVersion(): string {
    return \Composer\InstalledVersions::getRootPackage()['version'];
  }

  public static function getContainer(): Container {
    return self::$container;
  }

  public static function getRequest(): Request {
    return self::$container->get(Request::class);
  }

  private function loadEnv(string $rootPath): void {
    $path = $rootPath . '/.env';

    if (!file_exists($path)) {
      return;
    }

    if (!is_readable($path)) {
      return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      if (strpos(trim($line), '#') === 0) {
        continue;
      }

      [$key, $value] = explode('=', $line, 2);
      $key = trim($key);
      $value = trim($value);

      if (preg_match('/"(.*)"/', $value, $matches)) {
        $value = $matches[1];
      } elseif (preg_match('/\'(.*)\'/', $value, $matches)) {
        $value = $matches[1];
      }

      if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
        putenv(sprintf('%s=%s', $key, $value));
        $_SERVER[$key] = $value;
        $_ENV[$key] = $value;
      }
    }
  }

  private function defineConstants(string $rootPath): void {
    define('NEWTRON_ROOT', $rootPath);
  }

  private function registerServices(): void {

  }
}
