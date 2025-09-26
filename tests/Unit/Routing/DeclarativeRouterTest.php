<?php
declare(strict_types=1);

namespace Tests\Unit\Routing;

use Newtron\Core\Routing\DeclarativeRouter;
use Newtron\Core\Routing\RouteDefinition;
use Tests\RoutingTestCase;

class DeclarativeRouterTest extends RoutingTestCase {
  public function setUp(): void {
    parent::setUp();
    $this->router = new DeclarativeRouter();
  }

  public function testLoadRoutes(): void {
    file_put_contents(
      $this->testRoutePath . '/web.php', 
      "<?php\nuse Newtron\\Core\\Routing\\Route;\nRoute::get('/test', function () { return 'test_value'; });"
    );

    $this->router->loadRoutes();

    $this->assertCount(1, $this->router->getRoutes());

    $route = $this->router->getRoutes()[0];

    $this->assertInstanceOf(RouteDefinition::class, $route);
    $this->assertEquals('GET', $route->getMethod());
    $this->assertEquals('/test', $route->getPattern());
    $this->assertEquals('test_value', ($route->getHandler())());
  }
}
