<?php
declare(strict_types=1);

namespace Newtron\Core\Container;

class ServiceProviderRegistry {
  protected Container $container;
  protected array $providers = [];

  public function __construct(Container $container) {
    $this->container = $container;
  }

  /**
   * Register a service provider
   *
   * Creates an instance of the given provider class and calls its register method
   * to bind services into the container. If the provider is already registered,
   * this method will return early without duplicating the registration.
   *
   * @param  string $provider The fully qualified class name of the service provider
   */
  public function register(string $provider): void {
    if (isset($this->providers[$provider])) {
      return;
    }

    $instance = new $provider($this->container);

    $instance->register($this->container);

    $this->providers[$provider] = $instance;
  }

  /**
   * Boot all registered service providers
   *
   * Calls the boot method on all registered providers. This should be called
   * after all providers have been registered to ensure all services are
   * available during the boot process.
   */
  public function boot(): void {
    foreach ($this->providers as $provider) {
      $provider->boot();
    }
  }
}
