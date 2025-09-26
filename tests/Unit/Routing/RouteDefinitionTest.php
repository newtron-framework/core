<?php
declare(strict_types=1);

namespace Tests\Unit\Routing;

use Newtron\Core\Routing\RouteDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Mocks\Middleware\SimpleMiddleware;

class RouteDefinitionTest extends TestCase {
  public function testAddMiddleware(): void {
    $route = new RouteDefinition('GET', '/test', function () {});
    $route->withMiddleware(SimpleMiddleware::class);

    $this->assertContains(SimpleMiddleware::class, $route->getMiddleware());
  }

  public function testMatches(): void {
    $route = new RouteDefinition('GET', '/test', function () {});

    $this->assertTrue($route->matches('get', '/test'));
  }

  public function testExtractParamsFromDynamic(): void {
    $route = new RouteDefinition('GET', '/test/{param}', function () {});

    $this->assertEquals(['param' => 'test_value'], $route->extractParams('/test/test_value'));
  }

  public function testExtractParamsFromStatic(): void {
    $route = new RouteDefinition('GET', '/test', function () {});

    $this->assertEquals([], $route->extractParams('/test'));
  }

  public function testNormalizePattern(): void {
    $route = new RouteDefinition('GET', '/test', function () {});

    $reflection = new \ReflectionClass($route);
    $method = $reflection->getMethod('normalizePattern');
    $method->setAccessible(true);

    $this->assertEquals('/test', $method->invoke($route, 'test/'));
  }

  public function testCompilePattern(): void {
    $route = new RouteDefinition('GET', '/test', function () {});

    $reflection = new \ReflectionClass($route);
    $method = $reflection->getMethod('compilePattern');
    $method->setAccessible(true);

    $this->assertEquals('#^/test$#', $method->invoke($route));
  }

  public function testCompilePatternDynamic(): void {
    $route = new RouteDefinition('GET', '/test/{param}', function () {});

    $reflection = new \ReflectionClass($route);
    $method = $reflection->getMethod('compilePattern');
    $method->setAccessible(true);

    $this->assertEquals('#^/test/([^/]+)$#', $method->invoke($route));
  }

  public function testCompilePatternDynamicWithOptionalParam(): void {
    $route = new RouteDefinition('GET', '/test/{param?}', function () {});

    $reflection = new \ReflectionClass($route);
    $method = $reflection->getMethod('compilePattern');
    $method->setAccessible(true);

    $this->assertEquals('#^/test(?:/([^/]+)|/)?$#', $method->invoke($route));
  }

  public function testExtractParamNames(): void {
    $route = new RouteDefinition('GET', '/test/{first}/{second?}', function () {});

    $reflection = new \ReflectionClass($route);
    $method = $reflection->getMethod('extractParamNames');
    $method->setAccessible(true);

    $this->assertEquals(['first', 'second'], $method->invoke($route));
  }

  public function testIsParamOptional(): void {
    $route = new RouteDefinition('GET', '/test/{first}/{second?}', function () {});

    $reflection = new \ReflectionClass($route);
    $method = $reflection->getMethod('isParamOptional');
    $method->setAccessible(true);

    $this->assertFalse($method->invoke($route, 'first'));
    $this->assertTrue($method->invoke($route, 'second'));
  }
}
