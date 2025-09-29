<?php
declare(strict_types=1);

namespace Tests\Unit\Http;

use Newtron\Core\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase {
  public function testGetMethod(): void {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $request = new Request();

    $this->assertEquals('GET', $request->getMethod());
  }

  public function testIsMethod(): void {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $request = new Request();

    $this->assertTrue($request->isMethod('get'));
  }

  public function testGetAllHeaders(): void {
    $_SERVER['HTTP_TEST_HEADER'] = 'test_value';
    $request = new Request();

    $this->assertEquals(['TEST-HEADER' => 'test_value'], $request->getHeaders());
  }

  public function testGetHeader(): void {
    $_SERVER['HTTP_TEST_HEADER'] = 'test_value';
    $request = new Request();

    $this->assertEquals('test_value', $request->getHeader('Test-Header'));
  }

  public function testGetAllCookies(): void {
    $_COOKIE['test-cookie'] = 'test_value';
    $request = new Request();

    $this->assertEquals(['test-cookie' => 'test_value'], $request->getCookies());
  }

  public function testGetCookies(): void {
    $_COOKIE['test-cookie'] = 'test_value';
    $request = new Request();

    $this->assertEquals('test_value', $request->getCookie('test-cookie'));
  }

  public function testGetUri(): void {
    $_SERVER['REQUEST_URI'] = '/test?param=value';
    $request = new Request();

    $this->assertEquals('/test?param=value', $request->getURI());
  }

  public function testGetPath(): void {
    $_SERVER['REQUEST_URI'] = '/test?param=value';
    $request = new Request();

    $this->assertEquals('/test', $request->getPath());
  }

  public function testGetUrl(): void {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['HTTP_HOST'] = 'test.com';
    $_SERVER['REQUEST_URI'] = '/test?param=value';
    $request = new Request();

    $this->assertEquals('https://test.com/test?param=value', $request->getURL());
  }

  public function testIsSecure(): void {
    $_SERVER['HTTPS'] = 'on';
    $request = new Request();

    $this->assertTrue($request->isSecure());
  }

  public function testGetAllQuery(): void {
    $_GET['first'] = 'first_value';
    $_GET['second'] = 'second_value';
    $request = new Request();

    $this->assertEquals(['first' => 'first_value', 'second' => 'second_value'], $request->query());
  }

  public function testGetQuery(): void {
    $_GET['first'] = 'first_value';
    $_GET['second'] = 'second_value';
    $request = new Request();

    $this->assertEquals('second_value', $request->query('second'));
  }

  public function testGetAllData(): void {
    $_POST['first'] = 'first_value';
    $_POST['second'] = 'second_value';
    $request = new Request();

    $this->assertEquals(['first' => 'first_value', 'second' => 'second_value'], $request->data());
  }

  public function testGetData(): void {
    $_POST['first'] = 'first_value';
    $_POST['second'] = 'second_value';
    $request = new Request();

    $this->assertEquals('second_value', $request->data('second'));
  }

  public function testIsAjax(): void {
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    $request = new Request();

    $this->assertTrue($request->isAjax());
  }

  public function testGetZeroIpWhenNotSet(): void {
    $request = new Request();

    $this->assertEquals('0.0.0.0', $request->getIP());
  }

  public function testGetIp(): void {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $request = new Request();

    $this->assertEquals('127.0.0.1', $request->getIP());
  }
}
