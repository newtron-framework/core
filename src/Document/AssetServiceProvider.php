<?php
declare(strict_types=1);

namespace Newtron\Core\Document;

use Newtron\Core\Container\Container;
use Newtron\Core\Container\ServiceProvider;

class AssetServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(AssetManager::class, function (Container $c) {
      return new AssetManager();
    });
  }
}
