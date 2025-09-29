<?php
declare(strict_types=1);

namespace Tests\Unit\Quark;

use Tests\QuarkTestCase;

class QuarkCompilerTest extends QuarkTestCase {
  public function testBuiltinDirectivesRegistered(): void {
    $reflection = new \ReflectionClass($this->compiler);
    $property = $reflection->getProperty('directives');
    $property->setAccessible(true);
    $directives = $property->getValue($this->compiler);

    $this->assertEquals(
      [
        'layout',
        'skip_root',
        'outlet',
        'slot',
        'endslot',
        'include',
        'if',
        'elseif',
        'else',
        'endif',
        'foreach',
        'endforeach',
        'set',
      ],
      array_keys($directives)
    );
  }

  public function testCompileSource(): void {
    $this->assertEquals(
      "<?php\necho '<div>';\necho \$__quark->escape(\$test);\necho '</div>';\n",
      $this->compiler->compile('<div>{{ test }}</div>')
    );
  }

  public function testAddDirective(): void {
    $this->compiler->addDirective('test', function ($args) {
      return "echo 'test_directive_ran';\n";
    });

    $reflection = new \ReflectionClass($this->compiler);
    $property = $reflection->getProperty('directives');
    $property->setAccessible(true);
    $directives = $property->getValue($this->compiler);

    $this->assertArrayHasKey('test', $directives);
    $this->assertEquals(
      "<?php\necho '<div>';\necho 'test_directive_ran';\necho '</div>';\n",
      $this->compiler->compile('<div>{% test %}</div>')
    );
  }

  public function testTokenizeSource(): void {
    $reflection = new \ReflectionClass($this->compiler);
    $method = $reflection->getMethod('tokenize');
    $method->setAccessible(true);

    $this->assertEquals(
      [
        ['type' => 'text', 'content' => '<div>'],
        ['type' => 'expression', 'content' => 'test'],
        ['type' => 'text', 'content' => '</div>'],
        ['type' => 'directive', 'name' => 'outlet', 'args' => ''],
      ],
      $method->invoke($this->compiler, '<div>{{ test }}</div>{% outlet %}')
    );
  }

  public function testParseDirective(): void {
    $reflection = new \ReflectionClass($this->compiler);
    $method = $reflection->getMethod('parseDirective');
    $method->setAccessible(true);

    $this->assertEquals(
      ['type' => 'directive', 'name' => 'outlet', 'args' => 'custom'],
      $method->invoke($this->compiler, 'outlet custom')
    );
  }

  public function testCompileExpression(): void {
    $reflection = new \ReflectionClass($this->compiler);
    $method = $reflection->getMethod('compileExpression');
    $method->setAccessible(true);

    $this->assertEquals(
      "echo \$__quark->escape(\$test);\n",
      $method->invoke($this->compiler, 'test')
    );
  }

  public function testCompilePipeExpression(): void {
    $reflection = new \ReflectionClass($this->compiler);
    $method = $reflection->getMethod('compilePipeExpression');
    $method->setAccessible(true);

    $expected = "\$__pipe_value = \$test;\n";
    $expected .= "\$__pipe_value = \$__quark->applyFilter('capitalize', \$__pipe_value);\n";
    $expected .= "echo \$__pipe_value;\n";

    $this->assertEquals($expected, $method->invoke($this->compiler, 'test | capitalize'));
  }

  public function testCompilePipeExpressionWithArgs(): void {
    $reflection = new \ReflectionClass($this->compiler);
    $method = $reflection->getMethod('compilePipeExpression');
    $method->setAccessible(true);

    $expected = "\$__pipe_value = \$test;\n";
    $expected .= "\$__pipe_value = \$__quark->applyFilter('truncate', \$__pipe_value, 20);\n";
    $expected .= "echo \$__pipe_value;\n";

    $this->assertEquals($expected, $method->invoke($this->compiler, 'test | truncate(20)'));
  }

  public function testCompilePipeExpressionWithMultipleFilters(): void {
    $reflection = new \ReflectionClass($this->compiler);
    $method = $reflection->getMethod('compilePipeExpression');
    $method->setAccessible(true);

    $expected = "\$__pipe_value = \$test;\n";
    $expected .= "\$__pipe_value = \$__quark->applyFilter('capitalize', \$__pipe_value);\n";
    $expected .= "\$__pipe_value = \$__quark->applyFilter('truncate', \$__pipe_value, 20);\n";
    $expected .= "echo \$__pipe_value;\n";

    $this->assertEquals(
      $expected,
      $method->invoke($this->compiler, 'test | capitalize | truncate(20)')
    );
  }

  public function testNormalizeVariable(): void {
    $reflection = new \ReflectionClass($this->compiler);
    $method = $reflection->getMethod('normalizeVariable');
    $method->setAccessible(true);

    $this->assertEquals(
      '$test',
      $method->invoke($this->compiler, 'test')
    );
  }

  public function testNormalizeVariableDotToProperty(): void {
    $reflection = new \ReflectionClass($this->compiler);
    $method = $reflection->getMethod('normalizeVariable');
    $method->setAccessible(true);

    $this->assertEquals(
      '$test->property',
      $method->invoke($this->compiler, 'test.property')
    );
  }

  public function testNormalizeVariableMethodCall(): void {
    $reflection = new \ReflectionClass($this->compiler);
    $method = $reflection->getMethod('normalizeVariable');
    $method->setAccessible(true);

    $this->assertEquals(
      '$test->method()',
      $method->invoke($this->compiler, 'test->method()')
    );
  }
}
