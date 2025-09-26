<?php
declare(strict_types=1);

namespace Tests\Unit\Routing;

use Newtron\Core\Routing\RouteCollection;
use Newtron\Core\Routing\RouteDefinition;
use PHPUnit\Framework\TestCase;

class RouteCollectionTest extends TestCase {
  private RouteCollection $collection;

  public function setUp(): void {
    parent::setUp();
    $this->collection = new RouteCollection();
  }

  public function testAddRoute(): void {
    $route = new RouteDefinition('GET', '/test', function () { return 'test_value'; });

    $this->collection->add($route);

    $this->assertContains($route, $this->collection->all());
  }

  public function testMatchRoute(): void {
    $this->collection->add(
      new RouteDefinition('POST', '/test', function () { return 'post_value'; })
    );
    $this->collection->add(
      new RouteDefinition('GET', '/test', function () { return 'get_value'; })
    );

    $route = $this->collection->match('GET', '/test');

    $this->assertInstanceOf(RouteDefinition::class, $route);
    $this->assertEquals('get_value', ($route->getHandler())());
  }

  public function testMatchDynamicRoute(): void {
    $this->collection->add(
      new RouteDefinition('GET', '/test/{value}', function ($value) { return $value; })
    );

    $route = $this->collection->match('GET', '/test/test_value');

    $this->assertInstanceOf(RouteDefinition::class, $route);
    $this->assertEquals('test_value', ($route->getHandler())('test_value'));
  }

  public function testMatchNonExistentRoute(): void {
    $route = $this->collection->match('GET', '/test');

    $this->assertNull($route);
  }
}
