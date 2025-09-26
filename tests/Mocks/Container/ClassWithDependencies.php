<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

class ClassWithDependencies {
  public SimpleClass $dependency;

  public function __construct(SimpleClass $dependency) {
    $this->dependency = $dependency;
  }
}
