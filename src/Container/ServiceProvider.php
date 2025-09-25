<?php
declare(strict_types=1);

namespace Newtron\Core\Container;

abstract class ServiceProvider {
  /**
   * Register service in the container
   *
   * This method is called during the registration phase and should be used
   * to bind services, singletons, and instances into the container. This
   * method should not attempt to use any services from the container as
   * other providers may not have been registered yet.
   *
   * @param  Container $container The service container instance
   */
  abstract public function register(Container $container): void;

  /**
   * Bootstrap any application services
   *
   * Called after all services are registered. Override this method in
   * concrete providers to perform any additional setup that requires
   * other services to be available. The default implementation does nothing.
   *
   * @return void
   */
  public function boot(): void {}
}
