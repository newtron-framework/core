<?php
declare(strict_types=1);

namespace Newtron\Core\Application;

use Newtron\Core\Container\Container;
use Newtron\Core\Container\ServiceProvider;
use Newtron\Core\Container\ServiceProviderRegistry;

class App {
  private static $instance;
  private static Container $container;
  private static ServiceProviderRegistry $serviceProviderRegistry;

  private function __construct() {
    $this->container = new Container();
    $this->serviceProviderRegistry = new ServiceProviderRegistry($this->container);
    $this->registerServices();
  }

  public static function getInstance(): self {
    if (!isset(self::$instance)) {
      self::$instance = new self;
    }

    return self::$instance;
  }

  public static function addServiceProvider(ServiceProvider $provider): void {
    self::$serviceProviderRegistry->register($provider);
  }

  public static function run(): void {
    self::$serviceProviderRegistry->boot();
  }

  public static function getVersion(): string {
    return \Composer\InstalledVersions::getRootPackage()['version'];
  }

  public static function getContainer(): Container {
    return self::$container;
  }

  private function registerServices(): void {

  }
}
