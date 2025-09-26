<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

class TestImplementation implements TestInterface {
  public string $value = 'default';

  public function doSomething(): string {
    return 'implementation';
  }
}
