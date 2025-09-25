<?php
declare(strict_types=1);

namespace Newtron\Core\Document;

use Newtron\Core\Container\Container;
use Newtron\Core\Container\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(Document::class, function (Container $c) {
      $assetManager = $c->get(AssetManager::class);

      return new Document($assetManager);
    });
  }
}
