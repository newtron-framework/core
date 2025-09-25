<?php
declare(strict_types=1);

namespace Tests\Mocks\Container;

class CallableTestClass {
  private SimpleClass $dependency;

  public function __construct(SimpleClass $dependency) {
    $this->dependency = $dependency;
  }

  public function testMethod(): string {
    return 'method_called';
  }

  public function methodWithDependency(SimpleClass $simple): string {
    return 'dependency_injected';
  }

  public function methodWithParameters(string $name, int $count = 25): string {
    return "Name: {$name}, Count: {$count}";
  }
}
