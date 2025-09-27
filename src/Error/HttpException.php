<?php
declare(strict_types=1);

namespace Newtron\Core\Error;

use Newtron\Core\Http\Status;

class HttpException extends \Exception {
  protected int $statusCode;

  public function __construct(Status|int $statusCode, $message = "", $code = 0, \Exception $previous = null) {
    parent::__construct($message, $code, $previous);
    if ($statusCode instanceof Status) {
      $statusCode = $statusCode->value;
    }
    $this->statusCode = $statusCode;
  }

  /**
   * Get the HTTP status code
   *
   * @return int The status code
   */
  public function getStatusCode(): int {
    return $this->statusCode;
  }
}
