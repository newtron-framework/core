<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

abstract class FileRoute {
  abstract public function render(mixed $data): mixed;
}
