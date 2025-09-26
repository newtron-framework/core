<?php
declare(strict_types=1);

namespace Tests\Unit\Application;

use Newtron\Core\Application\App;
use Newtron\Core\Application\Config;
use Newtron\Core\Error\HttpException;
use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;
use Newtron\Core\Middleware\MiddlewareInterface;
use Newtron\Core\Routing\AbstractRouter;
use Newtron\Core\Routing\RouteDefinition;
use Tests\AppTestCase;

class AppTest extends AppTestCase {
  public function testAppInitializes(): void {
    $this->createTestConfig('app', ['name' => 'Newtron', 'debug' => false]);
    $app = App::create($this->testRootPath);

    $this->assertInstanceOf(App::class, $app);
  }

  public function testConstantsAreDefined(): void {
    $this->createTestConfig('app', ['name' => 'Newtron', 'debug' => false]);
    $app = App::create($this->testRootPath);

    $this->assertTrue(defined('NEWTRON_ROOT'));
    $this->assertTrue(defined('NEWTRON_CACHE'));
    $this->assertTrue(defined('NEWTRON_CONFIG'));
    $this->assertTrue(defined('NEWTRON_LOGS'));
    $this->assertTrue(defined('NEWTRON_ROUTES'));
    $this->assertTrue(defined('NEWTRON_TEMPLATES'));
  }

  public function testConfigIsLoaded(): void {
    $appConfig = ['name' => 'Testing', 'debug' => true];
    $routingConfig = ['mode' => 'file'];

    $this->createTestConfig('app', $appConfig);
    $this->createTestConfig('routing', $routingConfig);
    App::create($this->testRootPath);

    $config = App::getConfig();
    $this->assertInstanceOf(Config::class, $config);
    $this->assertEquals(true, $config->get('app.debug'));
    $this->assertEquals('Testing', $config->get('app.name'));
    $this->assertEquals('file', $config->get('routing.mode'));
  }

  public function testEnvIsLoaded(): void {
    $this->createTestEnv([
      'TEST_VAR' => 'test_value',
      'QUOTED_VAR' => '"quoted_value"',
      'SINGLE_QUOTED_VAR' => "'single_quoted'",
      '# COMMENT_VAR' => 'should_be_ignored'
    ]);

    App::create($this->testRootPath);

    $this->assertEquals('test_value', $_ENV['TEST_VAR']);
    $this->assertEquals('test_value', $_SERVER['TEST_VAR']);
    $this->assertEquals('quoted_value', $_ENV['QUOTED_VAR']);
    $this->assertEquals('single_quoted', $_ENV['SINGLE_QUOTED_VAR']);
    $this->assertArrayNotHasKey('# COMMENT_VAR', $_ENV);
  }

  public function testEnvironmentVariablesDoNotOverrideExisting(): void {
    $this->createTestConfig('app', ['debug' => false]);
    
    $_ENV['EXISTING_VAR'] = 'original_value';
    $_SERVER['EXISTING_VAR'] = 'original_value';
    
    $this->createTestEnv(['EXISTING_VAR' => 'new_value']);
    App::create($this->testRootPath);
    
    $this->assertEquals('original_value', $_ENV['EXISTING_VAR']);
    $this->assertEquals('original_value', $_SERVER['EXISTING_VAR']);
  }

  public function testAddGlobalMiddleware(): void {
    $this->createTestConfig('app', ['debug' => false]);
    App::create($this->testRootPath);
    
    $middlewareClass = new class implements MiddlewareInterface {
      public function handle(Request $request, callable $next): Response {
        return $next($request);
      }
    };
    
    $middlewareName = get_class($middlewareClass);
    App::addGlobalMiddleware($middlewareName);
    
    $middleware = App::getGlobalMiddleware();
    $this->assertContains($middlewareName, $middleware);
  }

  public function testRunWithValidRoute(): void {
    $this->createTestConfig('app', ['debug' => false]);
    
    $mockRouter = $this->createMock(AbstractRouter::class);
    $mockRoute = $this->createMock(RouteDefinition::class);
    $mockResponse = $this->createMock(Response::class);
    
    $mockRouter
      ->expects($this->once())
      ->method('dispatch')
      ->willReturn($mockRoute);
              
    $mockRouter
      ->expects($this->once())
      ->method('execute')
      ->with($mockRoute, $this->isInstanceOf(Request::class))
      ->willReturn($mockResponse);
              
    $mockResponse
      ->expects($this->once())
      ->method('send');
    
    App::create($this->testRootPath);
    App::getContainer()->instance(AbstractRouter::class, $mockRouter);
    
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/test';
    
    App::run();
  }

  public function testRunThrowsNotFoundExceptionForNoRoute(): void {
    $this->createTestConfig('app', ['debug' => false]);
    
    $mockRouter = $this->createMock(AbstractRouter::class);
    $mockRouter
      ->expects($this->once())
      ->method('dispatch')
      ->willReturn(null);
    
    App::create($this->testRootPath);
    App::getContainer()->instance(AbstractRouter::class, $mockRouter);
    
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/nonexistent';
    
    $this->expectException(HttpException::class);
    
    App::run();
  }
}
