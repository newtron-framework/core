<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

use Newtron\Core\Container\Container;

class ServiceProvider {
  private Container $container;

  public function __construct(Container $container) {
    $this->container = $container;
  }

  public function register(Container $container): void {
    $this->container->singleton(TestInterface::class, TestImplementation::class);
  }

  public function boot(): void {
    $instance = $this->container->get(TestInterface::class);
    $instance->value = 'boot_called';
  }
}
