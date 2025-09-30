<?php
declare(strict_types=1);

namespace Tests\Mocks\Quark;

class NestedObject {
  public string $property = 'Nested Property';

  public function method(): string {
    return 'Nested Method';
  }
}
