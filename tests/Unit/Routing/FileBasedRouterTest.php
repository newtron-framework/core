<?php
declare(strict_types=1);

namespace Tests\Unit\Routing;

use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;
use Newtron\Core\Routing\FileBasedRouter;
use Newtron\Core\Routing\RouteDefinition;
use Tests\RoutingTestCase;

class FileBasedRouterTest extends RoutingTestCase {
  public function setUp(): void {
    parent::setUp();
    $this->router = new FileBasedRouter();
  }

  public function testLoadRoutes(): void {
    $file = "<?php\nuse Newtron\\Core\\Routing\\FileRoute;\n";
    $file .= "use Newtron\\Core\\Http\\Request;\n";
    $file .= "class TestRoute extends FileRoute {\n";
    $file .= "public function get(): array {\nreturn ['test' => 'test_value'];\n}\n";
    $file .= "public function render(mixed \$data): mixed {\nreturn \$data['test'];\n}\n";
    $file .= "}\nreturn [\nnew TestRoute()\n];";
    file_put_contents($this->testRoutePath . '/test.php', $file);

    $this->router->loadRoutes();

    $this->assertCount(1, $this->router->getRoutes());

    $route = $this->router->getRoutes()[0];

    $this->assertInstanceOf(RouteDefinition::class, $route);
    $this->assertEquals('GET', $route->getMethod());
    $this->assertEquals('/test', $route->getPattern());
    $this->assertEquals('test_value', ($route->getHandler())([]));
  }

  public function testExecute(): void {
    $file = "<?php\nuse Newtron\\Core\\Routing\\FileRoute;\n";
    $file .= "use Newtron\\Core\\Http\\Request;\n";
    $file .= "class ExecuteRoute extends FileRoute {\n";
    $file .= "public function get(): array {\nreturn ['test' => 'test_value'];\n}\n";
    $file .= "public function render(mixed \$data): mixed {\nreturn \$data['test'];\n}\n";
    $file .= "}\nreturn [\nnew ExecuteRoute()\n];";
    file_put_contents($this->testRoutePath . '/test.php', $file);

    $this->router->loadRoutes();

    $this->assertCount(1, $this->router->getRoutes());

    $route = $this->router->getRoutes()[0];

    $_SERVER['REQUEST_URI'] = '/test';
    $request = new Request();
    $params = $route->extractParams($request->getPath());
    $route->setParams($params);
    $result = $this->router->execute($route, $request);

    $this->assertInstanceOf(Response::class, $result);
    $this->assertEquals('test_value', $result->getContent());
  }

  public function testExecuteDynamicRoute(): void {
    $file = "<?php\nuse Newtron\\Core\\Routing\\FileRoute;\n";
    $file .= "use Newtron\\Core\\Http\\Request;\n";
    $file .= "class DynamicRoute extends FileRoute {\n";
    $file .= "public function get(\$value): array {\nreturn ['test' => \$value ?? 'test_value'];\n}\n";
    $file .= "public function render(mixed \$data): mixed {\nreturn \$data['test'];\n}\n";
    $file .= "}\nreturn [\nnew DynamicRoute()\n];";
    file_put_contents($this->testRoutePath . '/test.[value].php', $file);

    $this->router->loadRoutes();

    $this->assertCount(1, $this->router->getRoutes());

    $route = $this->router->getRoutes()[0];

    $this->assertInstanceOf(RouteDefinition::class, $route);

    $_SERVER['REQUEST_URI'] = '/test/dynamic_value';
    $request = new Request();
    $params = $route->extractParams($request->getPath());
    $route->setParams($params);
    $result = $this->router->execute($route, $request);

    $this->assertInstanceOf(Response::class, $result);
    $this->assertEquals('dynamic_value', $result->getContent());
  }
}
