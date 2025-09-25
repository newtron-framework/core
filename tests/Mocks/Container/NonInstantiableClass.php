<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

abstract class NonInstantiableClass {
  abstract public function abstractMethod(): void;
}
