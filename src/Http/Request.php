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
    $this->server = $_SERVER ?? [];
    $this->method = $_SERVER['REQUEST_METHOD'];
    $this->headers = $this->parseHeaders();
    $this->cookies = $_COOKIE ?? [];
    $this->uri = $_SERVER['REQUEST_URI'];
    $this->get = $_GET ?? [];
    $this->post = $_POST ?? [];
    $this->files = $_FILES ?? [];
    $this->body = null;
  }

  /**
   * Get the HTTP request method
   *
   * @return string The request method
   */
  public function getMethod(): string {
    return $this->method;
  }
  
  /**
   * Check if the request is a given HTTP method
   *
   * @param  string $method The method to check
   * @return bool True if the request method matches, false otherwise
   */
  public function isMethod(string $method): bool {
    return $this->method === strtoupper($method);
  }

  /**
   * Get the request headers
   *
   * @return array Array of headers as $name => $value
   */
  public function getHeaders(): array {
    return $this->headers;
  }

  /**
   * Get a specific request header
   *
   * @param  string  $name    The name of the header to get 
   * @param  ?string $default The value to return if the header is not set
   * @return ?string The value of the header, or $default if it is not set
   */
  public function getHeader(string $name, ?string $default = null): ?string {
    return array_change_key_case($this->headers, CASE_LOWER)[strtolower($name)] ?? $default;
  }

  /**
   * Get the request cookies
   *
   * @return array Array of cookie data
   */
  public function getCookies(): array {
    return $this->cookies;
  }

  /**
   * Get a specific request cookie
   *
   * @param  string  $key     The key of the cookie to get 
   * @param  ?string $default The value to return if the cookie is not set
   * @return ?string The value of the cookie, or $default if it is not set
   */
  public function getCookie(string $key, mixed $default = null): mixed {
    return $this->cookies[$key] ?? $default;
  }

  /**
   * Get the request URI
   *
   * @return string The request URI
   */
  public function getURI(): string {
    return $this->uri;
  }

  /**
   * Get the request path
   *
   * @return string The request path
   */
  public function getPath(): string {
    return parse_url($this->uri, PHP_URL_PATH) ?? '/';
  }

  /**
   * Get the full request URL
   *
   * @return string The request URL
   */
  public function getURL(): string {
    $scheme = $this->isSecure() ? 'https' : 'http';
    $host = $this->server['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . $this->getURI();
  }

  /**
   * Check whether the request is secure (HTTPS)
   *
   * @return bool True if the request is secure, false otherwise
   */
  public function isSecure(): bool {
    return (
      (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ||
      (!empty($this->server['SERVER_PORT']) && $this->server['SERVER_PORT'] == 443) ||
      (!empty($this->server['HTTP_X_FORWARDED_PROTO']) && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https')
    );
  }
  
  /**
   * Get the request query parameters, or a specific query parameter
   *
   * @param  ?string $key     If set, the query parameter key to get
   * @param  ?string $default The value to return if the query parameter is not set
   * @return mixed If $key is set, the matching query parameter or null if it does not exist. The array of query parameters otherwise
   */
  public function query(?string $key = null, ?string $default = null): mixed {
    if ($key === null) {
      return $this->get;
    }

    return $this->get[$key] ?? $default;
  }

  /**
   * Get the request POST data, or a specific POST parameter
   *
   * @param  ?string $key     If set, the POST parameter key to get
   * @param  ?string $default The value to return if the POST parameter is not set
   * @return mixed If $key is set, the matching POST parameter or null if it does not exist. The array of POST parameters otherwise
   */
  public function data(?string $key = null, ?string $default = null): mixed {
    if ($key === null) {
      return $this->post;
    }

    return $this->post[$key] ?? $default;
  }

  /**
   * Get the request input data (GET or POST), or a specific input parameter
   *
   * @param  ?string $key     If set, the input parameter key to get
   * @param  mixed $default The value to return if the input parameter is not set
   * @return mixed If $key is set, the matching input parameter or null if it does not exist. The array of input parameters otherwise
   */
  public function input(?string $key = null, mixed $default = null): mixed {
    if ($key === null) {
      return array_merge($this->get, $this->post);
    }

    return $this->post[$key] ?? $this->get[$key] ?? $default;
  }

  /**
   * Get uploaded files from the request
   *
   * @return array Array of file data
   */
  public function getFiles(): array {
    return $this->files;
  }

  /**
   * Get an uploaded file from the request
   *
   * @param  string $key The file key
   * @return ?array File data if exists, null otherwise
   */
  public function getFile(string $key): ?array {
    return $this->files[$key] ?? null;
  }

  /**
   * Get the request body
   *
   * @return string The request body
   */
  public function getBody(): string {
    if ($this->body === null) {
      $this->body = file_get_contents('php://input');
    }

    return $this->body;
  }

  /**
   * Parse the request body as JSON
   *
   * @return ?array The parsed request body
   */
  public function getJson(): ?array {
    $body = $this->getBody();
    if (empty($body)) {
      return null;
    }

    return json_decode($body, true);
  }

  /**
   * Check if the request is an AJAX request
   *
   * @return bool True if the request is an AJAX request, false otherwise
   */
  public function isAjax(): bool {
    return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
  }

  /**
   * Try to get the remote address from the request
   *
   * @return string The remote address from the request 
   */
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

  /**
   * Get the request user agent
   *
   * @return string The user agent string
   */
  public function getUserAgent(): string {
    return $this->server['HTTP_USER_AGENT'] ?? '';
  }

  /**
   * Parse the request headers
   *
   * @return array Array of headers as $name => $value
   */
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
