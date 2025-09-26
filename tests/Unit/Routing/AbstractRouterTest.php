<?php
declare(strict_types=1);

namespace Tests\Unit\Routing;

use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;
use Newtron\Core\Routing\AbstractRouter;
use Newtron\Core\Routing\RouteDefinition;
use Tests\Mocks\Routing\SimpleRouter;
use Tests\RoutingTestCase;

class AbstractRouterTest extends RoutingTestCase {
  public function setUp(): void {
    parent::setUp();
    $this->router = new SimpleRouter();
  }

  public function testRegisterGet(): void {
    $route = $this->router->get('/test', function () {
      return 'test_value'; 
    });

    $this->assertInstanceOf(RouteDefinition::class, $route);
    $this->assertEquals('GET', $route->getMethod());
    $this->assertEquals('/test', $route->getPattern());
    $this->assertEquals('test_value', ($route->getHandler())());
    $this->assertContains($route, $this->router->getRoutes());
  }

  public function testRegisterPost(): void {
    $route = $this->router->post('/test', function () {
      return 'test_value'; 
    });

    $this->assertInstanceOf(RouteDefinition::class, $route);
    $this->assertEquals('POST', $route->getMethod());
    $this->assertEquals('/test', $route->getPattern());
    $this->assertEquals('test_value', ($route->getHandler())());
    $this->assertContains($route, $this->router->getRoutes());
  }

  public function testRegisterPut(): void {
    $route = $this->router->put('/test', function () {
      return 'test_value'; 
    });

    $this->assertInstanceOf(RouteDefinition::class, $route);
    $this->assertEquals('PUT', $route->getMethod());
    $this->assertEquals('/test', $route->getPattern());
    $this->assertEquals('test_value', ($route->getHandler())());
    $this->assertContains($route, $this->router->getRoutes());
  }

  public function testRegisterPatch(): void {
    $route = $this->router->patch('/test', function () {
      return 'test_value'; 
    });

    $this->assertInstanceOf(RouteDefinition::class, $route);
    $this->assertEquals('PATCH', $route->getMethod());
    $this->assertEquals('/test', $route->getPattern());
    $this->assertEquals('test_value', ($route->getHandler())());
    $this->assertContains($route, $this->router->getRoutes());
  }

  public function testRegisterDelete(): void {
    $route = $this->router->delete('/test', function () {
      return 'test_value'; 
    });

    $this->assertInstanceOf(RouteDefinition::class, $route);
    $this->assertEquals('DELETE', $route->getMethod());
    $this->assertEquals('/test', $route->getPattern());
    $this->assertEquals('test_value', ($route->getHandler())());
    $this->assertContains($route, $this->router->getRoutes());
  }

  public function testRegisterAny(): void {
    $routes = $this->router->any('/test', function () {
      return 'test_value'; 
    });

    $routeMethods = [];
    foreach ($routes as $route) {
      $this->assertContains($route, $this->router->getRoutes());
      $routeMethods[] = $route->getMethod();
    }

    $this->assertEqualsCanonicalizing(
      ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
      $routeMethods
    );
  }

  public function testRegisterMatch(): void {
    $routes = $this->router->match(['GET', 'POST'], '/test', function () {
      return 'test_value'; 
    });

    $routeMethods = [];
    foreach ($routes as $route) {
      $this->assertContains($route, $this->router->getRoutes());
      $routeMethods[] = $route->getMethod();
    }

    $this->assertEqualsCanonicalizing(['GET', 'POST'], $routeMethods);
  }

  public function testRegisterGroup(): void {
    $this->router->group(['prefix' => '/prefix'], function (AbstractRouter $r) {
      $r->get('/child', function () {
        return 'test_value';
      });
    }); 

    $this->assertCount(1, $this->router->getRoutes());

    $route = $this->router->getRoutes()[0];

    $this->assertEquals('/prefix/child', $route->getPattern());
    $this->assertEquals('test_value', ($route->getHandler())());
  }

  public function testDispatch(): void {
    $_SERVER['REQUEST_URI'] = '/test';
    $request = new Request();

    $this->router->get('/test', function () {
      return 'test_value';
    });

    $route = $this->router->dispatch($request);

    $this->assertInstanceOf(RouteDefinition::class, $route);
    $this->assertEquals('/test', $route->getPattern());
  }
  
  public function testDispatchWithNoMatch(): void {
    $_SERVER['REQUEST_URI'] = '/test';
    $request = new Request();

    $this->assertNull($this->router->dispatch($request));
  }

  public function testExecute(): void {
    $_SERVER['REQUEST_URI'] = '/test';
    $request = new Request();

    $route = $this->router->get('/test', function () {
      return 'test_value';
    });

    $result = $this->router->execute($route, $request);

    $this->assertInstanceOf(Response::class, $result);
    $this->assertEquals('test_value', $result->getContent());
  }

  public function testExecuteDynamicRoute(): void {
    $_SERVER['REQUEST_URI'] = '/test/test_value';
    $request = new Request();

    $route = $this->router->get('/test/{value}', function (string $value) {
      return $value;
    });
    $params = $route->extractParams($request->getPath());
    $route->setParams($params);

    $result = $this->router->execute($route, $request);

    $this->assertInstanceOf(Response::class, $result);
    $this->assertEquals('test_value', $result->getContent());
  }

  public function testNormalizeResponseWithResponse(): void {
    $reflection = new \ReflectionClass($this->router);
    $method = $reflection->getMethod('normalizeResponse');
    $method->setAccessible(true);

    $response = new Response();
    $result = $method->invoke($this->router, $response);

    $this->assertEquals($response, $result);
  }

  public function testNormalizeResponseWithArray(): void {
    $reflection = new \ReflectionClass($this->router);
    $method = $reflection->getMethod('normalizeResponse');
    $method->setAccessible(true);

    $result = $method->invoke($this->router, ['test' => 'test']);

    $this->assertInstanceOf(Response::class, $result);
    $this->assertEquals('{"test":"test"}', $result->getContent());
  }

  public function testNormalizeResponseWithString(): void {
    $reflection = new \ReflectionClass($this->router);
    $method = $reflection->getMethod('normalizeResponse');
    $method->setAccessible(true);

    $result = $method->invoke($this->router, 'test');

    $this->assertInstanceOf(Response::class, $result);
    $this->assertEquals('test', $result->getContent());
  }

  public function testNormalizeResponseWithNull(): void {
    $reflection = new \ReflectionClass($this->router);
    $method = $reflection->getMethod('normalizeResponse');
    $method->setAccessible(true);

    $result = $method->invoke($this->router, null);

    $this->assertInstanceOf(Response::class, $result);
    $this->assertEquals('', $result->getContent());
    $this->assertEquals(204, $result->getStatusCode());
  }
}
