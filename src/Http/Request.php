<?php
declare(strict_types=1);

namespace Newtron\Core\Http;

class Request {
  protected string $method;
  protected array $headers;
  protected array $cookies;
  protected array $server;
  protected string $uri;
  protected array $get;
  protected array $post;
  protected array $files;
  protected ?string $body;

  public function __construct() {
    $this->method = $_SERVER['REQUEST_METHOD'];
    $this->headers = $this->parseHeaders();
    $this->cookies = $_COOKIE ?? [];
    $this->server = $_SERVER ?? [];
    $this->uri = $_SERVER['REQUEST_URI'];
    $this->get = $_GET ?? [];
    $this->post = $_POST ?? [];
    $this->files = $_FILES ?? [];
    $this->body = null;
  }

  public function getMethod(): string {
    return $this->method;
  }
  
  public function isMethod(string $method): bool {
    return $this->method === strtoupper($method);
  }

  public function getHeaders(): array {
    return $this->headers;
  }

  public function getHeader(string $name, ?string $default = null): ?string {
    return array_change_key_case($this->headers, CASE_LOWER)[strtolower($name)] ?? $default;
  }

  public function getCookies(): array {
    return $this->cookies;
  }

  public function getCookie(string $key, mixed $default = null): mixed {
    return $this->cookies[$key] ?? $default;
  }

  public function getURI(): string {
    return $this->uri;
  }

  public function getPath(): string {
    return parse_url($this->uri, PHP_URL_PATH) ?? '/';
  }

  public function getURL(): string {
    $scheme = $this->isSecure() ? 'https' : 'http';
    $host = $this->server['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . $this->getURI();
  }

  public function isSecure(): bool {
    return (
      (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ||
      (!empty($this->server['SERVER_PORT']) && $this->server['SERVER_PORT'] == 443) ||
      (!empty($this->server['HTTP_X_FORWARDED_PROTO']) && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https')
    );
  }
  
  public function query(?string $key = null, ?string $default = null): mixed {
    if ($key === null) {
      return $this->get;
    }

    return $this->get[$key] ?? $default;
  }

  public function data(?string $key = null, ?string $default = null): mixed {
    if ($key === null) {
      return $this->post;
    }

    return $this->post[$key] ?? $default;
  }

  public function input(?string $key = null, mixed $default = null): mixed {
    if ($key === null) {
      return array_merge($this->get, $this->post);
    }

    return $this->post[$key] ?? $this->get[$key] ?? $default;
  }

  public function getFiles(): array {
    return $this->files;
  }

  public function getFile(string $key): ?array {
    return $this->files[$key] ?? null;
  }

  public function getBody(): string {
    if ($this->body === null) {
      $this->body = file_get_contents('php://input');
    }

    return $this->body;
  }

  public function getJson(): ?array {
    $body = $this->getBody();
    if (empty($body)) {
      return null;
    }

    return json_decode($body, true);
  }

  public function isAjax(): bool {
    return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
  }

  public function getIP(): string {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

    foreach ($ipKeys as $key) {
      if (!empty($this->server[$key])) {
        $ips = explode(',', $this->server[$key]);
        return trim($ips[0]);
      }
    }

    return '0.0.0.0';
  }

  public function getUserAgent(): string {
    return $this->server['HTTP_USER_AGENT'] ?? '';
  }

  protected function parseHeaders(): array {
    $headers = [];

    foreach ($this->server as $key => $value) {
      if (strpos($key, 'HTTP_') === 0) {
        $headerName = str_replace('_', '-', substr($key, 5));
        $headers[$headerName] = $value;
      }
    }

    if (isset($this->server['CONTENT_TYPE'])) {
      $headers['Content-Type'] = $this->server['CONTENT_TYPE'];
    }
    if (isset($this->server['CONTENT_LENGTH'])) {
      $headers['Content-Length'] = $this->server['CONTENT_LENGTH'];
    }

    return $headers;
  }
}
