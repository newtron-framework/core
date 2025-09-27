<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

class CircularDependencyB {
  public CircularDependencyA $dependency;

  public function __construct(CircularDependencyA $dependency) {
    $this->dependency = $dependency;
  }
}
