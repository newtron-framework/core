<?php
declare(strict_types=1);

namespace Newtron\Core\Routing;

abstract class FileRoute {
  public function get(): mixed {
    throw new \Exception('Method not allowed');
  }

  public function post(): mixed {
    throw new \Exception('Method not allowed');
  }

  public function put(): mixed {
    throw new \Exception('Method not allowed');
  }

  public function patch(): mixed {
    throw new \Exception('Method not allowed');
  }

  public function delete(): mixed {
    throw new \Exception('Method not allowed');
  }

  abstract public function render(mixed $data): mixed;
}
