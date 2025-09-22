<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

class DeclarativeRouter extends AbstractRouter {
  public function loadRoutes(): void {
    $files = glob(NEWTRON_ROUTES . '/*.php');

    foreach ($files as $file) {
      require_once $file;
    }
  }
}
