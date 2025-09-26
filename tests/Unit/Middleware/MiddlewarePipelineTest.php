<?php
declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;
use Newtron\Core\Middleware\MiddlewareInterface;
use Newtron\Core\Middleware\MiddlewarePipeline;
use PHPUnit\Framework\TestCase;
use Tests\Mocks\Middleware\MiddlewareWithoutInterface;
use Tests\Mocks\Middleware\SimpleMiddleware;

class MiddlewarePipelineTest extends TestCase {
  private MiddlewarePipeline $pipeline;

  public function setUp(): void {
    parent::setUp();
    $this->pipeline = new MiddlewarePipeline();
  }

  public function testPipeMiddleware(): void {
    $mockMiddleware = $this->createMock(SimpleMiddleware::class);

    $this->pipeline->pipe(SimpleMiddleware::class);

    $reflection = new \ReflectionClass($this->pipeline);
    $property = $reflection->getProperty('middleware');
    $property->setAccessible(true);
    $middleware = $property->getValue($this->pipeline);

    $this->assertContains(SimpleMiddleware::class, $middleware);
  }

  public function testPipeNonExistentMiddleware(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Middleware \'NonExistentMiddleware\' not found');

    $this->pipeline->pipe('NonExistentMiddleware');
  }

  public function testPipeMiddlewareInterfaceNotImplemented(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('must implement MiddlewareInterface');

    $this->pipeline->pipe(MiddlewareWithoutInterface::class);
  }

  public function testProcessMiddleware(): void {
    $this->pipeline->pipe(SimpleMiddleware::class);
    $result = $this->pipeline->process(new Request(), function () {
      return Response::create('default');
    });

    $this->assertInstanceOf(Response::class, $result);
    $this->assertEquals('middleware_called', $result->getContent());
  }
}
