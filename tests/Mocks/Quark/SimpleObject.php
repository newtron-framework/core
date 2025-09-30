<?php
declare(strict_types=1);

namespace Tests\Mocks\Quark;

class SimpleObject {
  public string $property = 'Test Property';
  public NestedObject $nestedObject;

  public function __construct() {
    $this->nestedObject = new NestedObject();
  }

  public function method(): string {
    return 'Test Method';
  }

  public function getNested(): NestedObject {
    return $this->nestedObject;
  }
}
