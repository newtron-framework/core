<?php
declare(strict_types=1);

namespace Newtron\Core\Quark;

use Newtron\Core\Application\App;
use Newtron\Core\Container\Container;
use Newtron\Core\Container\ServiceProvider;

class QuarkServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(QuarkEngine::class, function (Container $c) {
      $debug = App::getConfig()->get('app.debug', false);

      return new QuarkEngine(NEWTRON_TEMPLATES, NEWTRON_CACHE, $debug);
    });
  }

  public function boot(): void {
    /** @var QuarkEngine $engine */
    $engine = App::getContainer()->get(QuarkEngine::class);

    $engine->setRootLayout('_root');
    $engine->addGlobal('request', App::getRequest());

    Quark::setEngine($engine);
  }
}
