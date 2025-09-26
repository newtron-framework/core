<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

class ClassWithBuiltinDependencies {
  public string $stringParam;
  public int $intParam;

  public function __construct(string $stringParam = 'default', int $intParam = 100) {
    $this->stringParam = $stringParam;
    $this->intParam = $intParam;
  }
}
