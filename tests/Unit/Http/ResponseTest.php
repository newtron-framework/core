<?php
declare(strict_types=1);

namespace Tests\Unit\Http;

use Newtron\Core\Http\Response;
use Newtron\Core\Http\Status;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase {
  private Response $response;

  public function setUp(): void {
    parent::setUp();
    $this->response = new Response();
  }

  public function testSetStatusCode(): void {
    $this->response->setStatusCode(Status::NOT_FOUND);

    $this->assertEquals(404, $this->response->getStatusCode());
  }

  public function testSetHeader(): void {
    $this->response->setHeaders(['test' => 'test_value']);

    $this->assertEquals('test_value', $this->response->getHeader('test'));
  }

  public function testSetContent(): void {
    $this->response->setContent('Test');

    $this->assertEquals('Test', $this->response->getContent());
  }

  public function testSetJson(): void {
    $this->response->setJson(['test' => 'test_value']);

    $this->assertEquals(200, $this->response->getStatusCode());
    $this->assertEquals('application/json', $this->response->getHeader('Content-Type'));
    $this->assertEquals('{"test":"test_value"}', $this->response->getContent());
  }

  public function testSetHtml(): void {
    $this->response->setHtml('<div>Test</div>');

    $this->assertEquals(200, $this->response->getStatusCode());
    $this->assertEquals('text/html; charset=utf-8', $this->response->getHeader('Content-Type'));
    $this->assertEquals('<div>Test</div>', $this->response->getContent());
  }

  public function testSetText(): void {
    $this->response->setText('Test');

    $this->assertEquals(200, $this->response->getStatusCode());
    $this->assertEquals('text/plain; charset=utf-8', $this->response->getHeader('Content-Type'));
    $this->assertEquals('Test', $this->response->getContent());
  }

  public function testRedirect(): void {
    $this->response->redirect('/test');

    $this->assertEquals(302, $this->response->getStatusCode());
    $this->assertEquals('/test', $this->response->getHeader('Location'));
  }

  public function testSetCookie(): void {
    $this->response->setCookie('test-cookie', 'test_value');

    $reflection = new \ReflectionClass($this->response);
    $property = $reflection->getProperty('cookies');
    $property->setAccessible(true);
    $cookies = $property->getValue($this->response);

    $this->assertContains(
      [
        'name' => 'test-cookie',
        'value' => 'test_value',
        'expires' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httpOnly' => true
      ],
      $cookies
    );
  }

  public function testSendResponse(): void {
    $this->response->setContent('Test Content');

    ob_start();
    $this->response->send();
    $result = ob_get_clean();

    $this->assertEquals('Test Content', $result);
  }

  public function testCreateResponse(): void {
    $response = Response::create('Test');

    $this->assertInstanceOf(Response::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('Test', $response->getContent());
  }

  public function testCreateJsonResponse(): void {
    $response = Response::createJson(['test' => 'test_value']);

    $this->assertInstanceOf(Response::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('application/json', $response->getHeader('Content-Type'));
    $this->assertEquals('{"test":"test_value"}', $response->getContent());
  }

  public function testCreateRedirectResponse(): void {
    $response = Response::createRedirect('/test');

    $this->assertInstanceOf(Response::class, $response);
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('/test', $response->getHeader('Location'));
  }
}
