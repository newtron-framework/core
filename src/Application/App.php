<?php
declare(strict_types=1);

namespace Newtron\Core\Application;

use Newtron\Core\Container\Container;
use Newtron\Core\Container\ServiceProviderRegistry;
use Newtron\Core\Error\ErrorHandler;
use Newtron\Core\Error\HttpException;
use Newtron\Core\Error\Logger;
use Newtron\Core\Http\Request;
use Newtron\Core\Http\Status;
use Newtron\Core\Middleware\MiddlewareInterface;
use Newtron\Core\Quark\QuarkServiceProvider;
use Newtron\Core\Routing\AbstractRouter;
use Newtron\Core\Routing\RouterServiceProvider;

class App {
  private static $instance;
  private static Logger $logger;
  private static ErrorHandler $errorHandler;
  private static Container $container;
  private static ServiceProviderRegistry $serviceProviderRegistry;
  private static Config $config;
  private static array $globalMiddleware = [];

  private function __construct(string $rootPath) {
    $this->loadEnv($rootPath);

    $this->defineConstants($rootPath);

    $this->loadConfig();

    static::$logger = new Logger(NEWTRON_LOGS);
    static::$errorHandler = new ErrorHandler(static::$logger, $this->getConfig()->get('app.debug', false));

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

  public static function addServiceProvider(string $provider): void {
    static::$serviceProviderRegistry->register($provider);
  }
  
  public static function addGlobalMiddleware(string $middleware): void {
    if (!class_exists($middleware)) {
      throw new \InvalidArgumentException("Middleware '{$middleware}' not found");
    }
    if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
      throw new \InvalidArgumentException("Middleware '{$middleware}' must implement MiddlewareInterface");
    }
    static::$globalMiddleware[] = $middleware;
  }

  public static function run(): void {
    static::$serviceProviderRegistry->boot();

    $request = new Request();
    static::getContainer()->instance(Request::class, $request);

    /** @var AbstractRouter $router */
    $router = static::getContainer()->get(AbstractRouter::class);

    $route = $router->dispatch($request);

    if (!$route) {
      static::getLogger()->debug($request->getPath());
      throw new HttpException(Status::NOT_FOUND, Status::NOT_FOUND->getText());
      return;
    }

    $router->execute($route, $request)->send();
  }

  public static function getVersion(): string {
    return \Composer\InstalledVersions::getRootPackage()['version'];
  }

  public static function getLogger(): Logger {
    return static::$logger;
  }

  public static function setErrorPage(Status|int $statusCode, string $template): void {
    static::$errorHandler->setErrorPage($statusCode, $template);
  }

  public static function getContainer(): Container {
    return static::$container;
  }

  public static function getConfig(): Config {
    return static::$config;
  }

  public static function getGlobalMiddleware(): array {
    return static::$globalMiddleware;
  }

  public static function getRequest(): Request {
    return static::getContainer()->get(Request::class);
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
    define('NEWTRON_CACHE', NEWTRON_ROOT . '/cache');
    define('NEWTRON_CONFIG', NEWTRON_ROOT . '/config');
    define('NEWTRON_LOGS', NEWTRON_ROOT . '/logs');
    define('NEWTRON_ROUTES', NEWTRON_ROOT . '/routes');
    define('NEWTRON_TEMPLATES', NEWTRON_ROOT . '/templates');
  }

  private function loadConfig(): void {
    $files = glob(NEWTRON_CONFIG . '/*.php');

    $items = [];
    foreach ($files as $file) {
      $name = basename($file, '.php');
      $items[$name] = require $file;
    }

    static::$config = new Config($items);
  }

  private function registerServices(): void {
    $this->addServiceProvider(RouterServiceProvider::class);
    $this->addServiceProvider(QuarkServiceProvider::class);
  }
}
