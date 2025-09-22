<?php
declare(strict_types=1);

namespace Newtron\Core\Http;

class Response {
  protected int $statusCode = Status::OK;
  protected array $headers = [];
  protected string $content = '';
  protected array $cookies = [];

  public function getStatusCode(): int {
    return $this->statusCode;
  }

  public function setStatusCode(int $statusCode): self {
    $this->statusCode = $statusCode;
    return $this;
  }

  public function getHeaders(): array {
    return $this->headers;
  }

  public function getHeader(string $name): ?string {
    return $this->headers[$name] ?? null;
  }

  public function setHeaders(array $headers): self {
    foreach ($headers as $name => $value) {
      $this->setHeader($name, $value);
    }
    return $this;
  }

  public function setHeader(string $name, string $value): self {
    $this->headers[$name] = $value;
    return $this;
  }

  public function getContent(): string {
    return $this->content;
  }

  public function setContent(string $content): self {
    $this->content = $content;
    return $this;
  }

  public function setJson(array $data, int $statusCode = Status::OK, int $options = 0): self {
    $this->setStatusCode($statusCode);
    $this->setHeader('Content-Type', 'application/json');
    $this->setContent(json_encode($data, $options));
    return $this;
  }

  public function setHtml(string $html, int $statusCode = Status::OK): self {
    $this->setStatusCode($statusCode);
    $this->setHeader('Content-Type', 'text/html; charset=utf-8');
    $this->setContent($html);
    return $this;
  }

  public function setText(string $text, int $statusCode = Status::OK): self {
    $this->setStatusCode($statusCode);
    $this->setHeader('Content-Type', 'text/plain; charset=utf-8');
    $this->setContent($text);
    return $this;
  }

  public function redirect(string $url, int $statusCode = Status::FOUND): self {
    $this->setStatusCode($statusCode);
    $this->setHeader('Location', $url);
    return $this;
  }

  public function setCookie(
    string $name,
    string $value,
    int $expires = 0,
    string $path = '/',
    string $domain = '',
    bool $secure = false,
    bool $httpOnly = true,
  ): self {
    $this->cookies[] = compact(['name', 'value', 'expires', 'path', 'domain', 'secure', 'httpOnly']);
    return $this;
  }

  public function send(): void {
    if (!headers_sent()) {
      http_response_code($this->statusCode);

      foreach ($this->headers as $name => $value) {
        header("{$name}: {$value}");
      }

      foreach ($this->cookies as $cookie) {
        setcookie(
          $cookie['name'],
          $cookie['value'],
          time() + $cookie['expires'],
          $cookie['path'],
          $cookie['domain'],
          $cookie['secure'],
          $cookie['httpOnly']
        );
      }
    }

    echo $this->content;
  }

  public static function create(string $content = '', int $statusCode = Status::OK, array $headers = []): self {
    $response = new self;
    $response->setContent($content);
    $response->setStatusCode($statusCode);
    $response->setHeaders($headers);
    return $response;
  }

  public static function createJson(array $data, int $statusCode = Status::OK, int $options = 0): self {
    return (new self)->setJson($data, $statusCode, $options);
  }

  public static function createRedirect(string $url, int $statusCode = Status::FOUND): self {
    return (new self)->redirect($url, $statusCode);
  }
}
