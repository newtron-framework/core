<?php
declare(strict_types=1);

namespace Newtron\Core\Container;

class ServiceProviderRegistry {
  protected Container $container;
  protected array $providers = [];

  public function __construct(Container $container) {
    $this->container = $container;
  }

  public function register(string $provider): void {
    if (isset($this->providers[$provider])) {
      return;
    }

    $instance = new $provider($this->container);

    $instance->register($this->container);

    $this->providers[$provider] = $instance;
  }

  public function boot(): void {
    foreach ($this->providers as $provider) {
      $provider->boot();
    }
  }
}
