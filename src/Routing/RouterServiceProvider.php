<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

use Newtron\Core\Application\App;
use Newtron\Core\Container\Container;
use Newtron\Core\Container\ServiceProvider;

class RouterServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(AbstractRouter::class, function (Container $c) {
      $mode = App::getConfig()->get('routing.mode');

      if (strtoupper($mode) === 'FILE') {
        return new FileBasedRouter();
      } else {
        return new DeclarativeRouter();
      }
    });
  }
}
