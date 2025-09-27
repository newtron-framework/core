<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

class CircularDependencyA {
  public CircularDependencyB $dependency;

  public function __construct(CircularDependencyB $dependency) {
    $this->dependency = $dependency;
  }
}
