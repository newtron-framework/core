<?php
declare(strict_types=1);

namespace Newtron\Core\Http;

class Response {
  protected int $statusCode = Status::OK->value;
  protected array $headers = [];
  protected string $content = '';
  protected array $cookies = [];

  /**
   * Get the HTTP status code
   *
   * @return int The status code
   */
  public function getStatusCode(): int {
    return $this->statusCode;
  }

  /**
   * Set the HTTP status code
   *
   * @param  Status|int $statusCode The status code
   * @return Response the response instance for chaining
   */
  public function setStatusCode(Status|int $statusCode): self {
    if ($statusCode instanceof Status) {
      $statusCode = $statusCode->value;
    }
    $this->statusCode = $statusCode;
    return $this;
  }

  /**
   * Get the current response headers
   *
   * @return array Array of headers as $name => $value
   */
  public function getHeaders(): array {
    return $this->headers;
  }

  /**
   * Get a response header
   *
   * @param  string $name The header to get
   * @return ?string The value of the header if it is set, null otherwise
   */
  public function getHeader(string $name): ?string {
    return $this->headers[$name] ?? null;
  }

  /**
   * Set the response headers
   *
   * @param  array $headers Array of headers to set, as $name => $value
   * @return Response The response instance for chaining
   */
  public function setHeaders(array $headers): self {
    foreach ($headers as $name => $value) {
      $this->setHeader($name, $value);
    }
    return $this;
  }

  /**
   * Set a response header
   *
   * @param  string $name  The header to set
   * @param  string $value The value to set
   * @return Response The response instance for chaining
   */
  public function setHeader(string $name, string $value): self {
    $this->headers[$name] = $value;
    return $this;
  }

  /**
   * Get the current response content
   *
   * @return string The response content
   */
  public function getContent(): string {
    return $this->content;
  }

  /**
   * Set the response content
   *
   * @param  string $content The response content
   * @return Response The response instance for chaining
   */
  public function setContent(string $content): self {
    $this->content = $content;
    return $this;
  }

  /**
   * Set the JSON content for the response
   *
   * @param  array      $data       Array of data to encode
   * @param  Status|int $statusCode The status code
   * @param  int        $options    Options for json_encode
   * @return Response The response instance for chaining
   */
  public function setJson(array $data, Status|int $statusCode = Status::OK, int $options = 0): self {
    if ($statusCode instanceof Status) {
      $statusCode = $statusCode->value;
    }
    $this->setStatusCode($statusCode);
    $this->setHeader('Content-Type', 'application/json');
    $this->setContent(json_encode($data, $options));
    return $this;
  }

  /**
   * Set the HTML content for the response
   *
   * @param  string     $html       The HTML content
   * @param  Status|int $statusCode The status code
   * @return Response The response instance for chaining
   */
  public function setHtml(string $html, Status|int $statusCode = Status::OK): self {
    if ($statusCode instanceof Status) {
      $statusCode = $statusCode->value;
    }
    $this->setStatusCode($statusCode);
    $this->setHeader('Content-Type', 'text/html; charset=utf-8');
    $this->setContent($html);
    return $this;
  }

  /**
   * Set the plaintext content for the response
   *
   * @param  string     $text       The plaintext content
   * @param  Status|int $statusCode The status code
   * @return Response The response instance for chaining
   */
  public function setText(string $text, Status|int $statusCode = Status::OK): self {
    if ($statusCode instanceof Status) {
      $statusCode = $statusCode->value;
    }
    $this->setStatusCode($statusCode);
    $this->setHeader('Content-Type', 'text/plain; charset=utf-8');
    $this->setContent($text);
    return $this;
  }

  /**
   * Set the redirect for the response
   *
   * @param  string     $url        The URL to redirect to
   * @param  Status|int $statusCode The status code
   * @return Response The response instance for chaining
   */
  public function redirect(string $url, Status|int $statusCode = Status::FOUND): self {
    if ($statusCode instanceof Status) {
      $statusCode = $statusCode->value;
    }
    $this->setStatusCode($statusCode);
    $this->setHeader('Location', $url);
    return $this;
  }

  /**
   * Set a cookie for the response
   *
   * @param  string $name     The cookie name
   * @param  string $value    The cookie value
   * @param  int    $expires  The expiry time (seconds)
   * @param  string $path     The cookie path
   * @param  string $domain   The cookie domain
   * @param  bool   $secure   Whether the cookie is secure
   * @param  bool   $httpOnly Whether the cookie is httpOnly
   * @return Response The response instance for chaining
   */
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

  /**
   * Send the response
   */
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

  /**
   * Create a new response
   *
   * @param  string     $content    The content to set
   * @param  Status|int $statusCode The status code
   * @param  array      $headers    Array of headers to set
   * @return Response The created response
   */
  public static function create(string $content = '', Status|int $statusCode = Status::OK, array $headers = []): self {
    if ($statusCode instanceof Status) {
      $statusCode = $statusCode->value;
    }
    $response = new self;
    $response->setContent($content);
    $response->setStatusCode($statusCode);
    $response->setHeaders($headers);
    return $response;
  }

  /**
   * Create a new JSON response
   *
   * @param  array      $data       Array of data to encode
   * @param  Status|int $statusCode The status code
   * @param  int        $options    Options for json_encode
   * @return Response The created JSON response
   */
  public static function createJson(array $data, Status|int $statusCode = Status::OK, int $options = 0): self {
    if ($statusCode instanceof Status) {
      $statusCode = $statusCode->value;
    }
    return (new self)->setJson($data, $statusCode, $options);
  }

  /**
   * Create a new redirect response
   *
   * @param  string     $url        The URL to redirect to
   * @param  Status|int $statusCode The status code
   * @return Response The created redirect response
   */
  public static function createRedirect(string $url, Status|int $statusCode = Status::FOUND): self {
    if ($statusCode instanceof Status) {
      $statusCode = $statusCode->value;
    }
    return (new self)->redirect($url, $statusCode);
  }
}
