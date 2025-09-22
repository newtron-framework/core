<?php
declare(strict_types=1);

namespace Newtron\Core\Container;

abstract class ServiceProvider {
  abstract public function register(Container $container): void;

  public function boot(): void {}
}
