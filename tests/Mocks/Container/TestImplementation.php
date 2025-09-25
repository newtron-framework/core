<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

class TestImplementation implements TestInterface {
  public function doSomething(): string {
    return 'implementation';
  }
}
