<?php
declare(strict_types=1);

namespace Newtron\Core\Error;

use Newtron\Core\Http\Response;
use Newtron\Core\Http\Status;
use Newtron\Core\Quark\Quark;

class ErrorHandler {
  private Logger $logger;
  private bool $debug;
  private $errorPages = [];

  public function __construct(Logger $logger, bool $debug = false) {
    $this->logger = $logger;
    $this->debug = $debug;

    set_error_handler([$this, 'handleError']);
    set_exception_handler([$this, 'handleException']);
    register_shutdown_function([$this, 'handleFatalError']);
  }

  public function setErrorPage(Status|int $statusCode, string $template): void {
    $this->errorPages[$statusCode] = $template;
  }

  public function handleError(int $severity, string $message, string $filename, int $lineno): bool {
    if (!(error_reporting() & $severity)) {
      return false;
    }

    $context = [
      'severity' => $severity,
      'file' => $filename,
      'line' => $lineno,
      'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
    ];

    $this->logger->error("PHP Error: $message", $context);

    throw new \ErrorException($message, 0, $severity, $filename, $lineno);
  }

  public function handleException(\Throwable $exception): void {
    $this->cleanOutputBuffers();


    try {
      if ($exception instanceof HttpException) {
        $this->handleHttpException($exception);
      } else {
        $this->logException($exception);
        $this->handleGenericException($exception);
      }
    } catch (\Throwable $renderException) {
      $this->logger->critical('Error rendering error page', [
        'original_exception' => $exception->getMessage(),
        'render_exception' => $renderException->getMessage()
      ]);

      $this->showBasicErrorPage($exception);
    }
  }

  public function handleFatalError(): void {
    $error = error_get_last();

    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
      $this->cleanOutputBuffers();

      $this->logger->critical('Fatal Error', [
        'message' => $error['message'],
        'file' => $error['file'],
        'line' => $error['line'],
        'type' => $error['type']
      ]);

      if ($this->debug) {
        $this->showDebugFatalError($error);
      } else {
        $this->showError(500);
      }
    }
  }

  private function cleanOutputBuffers(): void {
    while (ob_get_level()) {
      ob_end_clean();
    }
  }

  private function logException(\Throwable $exception): void {
    $context = [
      'exception' => get_class($exception),
      'message' => $exception->getMessage(),
      'file' => $exception->getFile(),
      'line' => $exception->getLine(),
      'code' => $exception->getCode(),
      'trace' => $exception->getTraceAsString(),
      'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
      'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
      'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'
    ];

    $this->logger->error('Uncaught Exception', $context);
  }

  private function handleHttpException(HttpException $exception): void {
    if ($this->debug) {
      $this->showDebugError($exception);
    } else {
      $this->showError($exception->getStatusCode());
    }
  }

  private function handleGenericException(\Throwable $exception): void {
    if ($this->debug) {
      $this->showDebugError($exception);
    } else {
      $this->showError(Status::INTERNAL_ERROR);
    }
  }

  private function showDebugError(\Throwable $exception): void {
    $trace = $exception->getTrace();

    ob_start();
    include dirname(__FILE__) . '/_debug-error.php';
    $page = ob_get_clean();

    Response::create($page, Status::INTERNAL_ERROR)->send();
  }

  private function showDebugFatalError(array $error): void {
    ob_start();
    include dirname(__FILE__) . '/_debug-fatal.php';
    $page = ob_get_clean();

    Response::create($page, Status::INTERNAL_ERROR)->send();
  }

  private function showError(Status|int $statusCode): void {
    $message = 'An error occurred';
    if ($statusCode instanceof Status) {
      $message = $statusCode->getText();
      $statusCode = $statusCode->value;
    } else {
      $code = Status::tryFrom($statusCode);
      if ($code) {
        $message = $code->getText();
      }
    }

    $page = null;
    if (isset($this->errorPages[$statusCode])) {
      try {
        $page = Quark::render($this->errorPages[$statusCode]);
      } catch (\Throwable $e) {}
    }

    if ($page === null) {
      ob_start();
      include dirname(__FILE__) . '/_default-error.php';
      $page = ob_get_clean();
    }

    Response::create($page, $statusCode)->send();
  }

  private function showBasicErrorPage(\Throwable $exception): void {
    http_response_code(500);
    echo "An error occurred. Please try again later.";
  }
}
