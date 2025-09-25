<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

class ClassWithUnionTypes {
  public function __construct(string|int $param) {
  }
}
