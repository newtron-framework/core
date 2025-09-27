<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

class ClassWithOptionalDependencies {
  public SimpleClass $required;
  public ?TestImplementation $optional;

  public function __construct(SimpleClass $required, ?TestImplementation $optional = null) {
    $this->required = $required;
    $this->optional = $optional;
  }
}
