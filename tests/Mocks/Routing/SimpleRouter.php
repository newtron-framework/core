<?php
declare(strict_types=1);

namespace Tests\Mocks\Routing;

use Newtron\Core\Routing\AbstractRouter;

class SimpleRouter extends AbstractRouter {
  public function loadRoutes(): void {
    return;
  }
}
