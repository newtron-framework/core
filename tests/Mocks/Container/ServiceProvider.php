<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

use Newtron\Core\Container\Container;

class ServiceProvider {
  private Container $container;

  public function __construct(Container $container) {
    $this->container = $container;
  }

  public function register(): void {
    $this->container->bind(TestInterface::class, TestImplementation::class);
  }
}
