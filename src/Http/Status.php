<?php
declare(strict_types=1);

namespace Newtron\Core\Http;

enum Status: int {
  case OK = 200;
  case CREATED = 201;
  case NO_CONTENT = 204;
  case MOVED_PERMANENTLY = 301;
  case FOUND = 302;
  case NOT_MODIFIED = 304;
  case BAD_REQUEST = 400;
  case UNAUTHORIZED = 401;
  case FORBIDDEN = 403;
  case NOT_FOUND = 404;
  case NOT_ALLOWED = 405;
  case TOO_MANY_REQUESTS = 429;
  case INTERNAL_ERROR = 500;
  case NOT_IMPLEMENTED = 501;
  case BAD_GATEWAY = 502;
  case UNAVAILABLE = 503;

  public function getText(): string {
    /* @var Status $this */
    return match($this) {
      Status::OK => 'OK',
      Status::CREATED => 'Created',
      Status::NO_CONTENT => 'No Content',
      Status::MOVED_PERMANENTLY => 'Moved Permanently',
      Status::FOUND => 'Found',
      Status::NOT_MODIFIED => 'Not Modified',
      Status::BAD_REQUEST => 'Bad Request',
      Status::UNAUTHORIZED => 'Unauthorized',
      Status::FORBIDDEN => 'Forbidden',
      Status::NOT_FOUND => 'Not Found',
      Status::NOT_ALLOWED => 'Method Not Allowed',
      Status::TOO_MANY_REQUESTS => 'Too Many Requests',
      Status::INTERNAL_ERROR => 'Internal Server Error',
      Status::NOT_IMPLEMENTED => 'Not Implemented',
      Status::BAD_GATEWAY => 'Bad Gateway',
      Status::UNAVAILABLE => 'Service Unavailable',
      default => '',
    };
  }
}
