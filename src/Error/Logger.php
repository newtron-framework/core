<?php
declare(strict_types=1);

namespace Newtron\Core\Error;

class Logger {
  const DEBUG = 'debug';
  const INFO = 'info';
  const WARNING = 'warning';
  const ERROR = 'error';
  const CRITICAL = 'critical';

  private string $logPath;
  private int $maxFileSize;
  private int $maxFiles;

  public function __construct(string $logPath = 'logs', $maxFileSize = 10485760, $maxFiles = 5) {
    $this->logPath = rtrim($logPath, '/');
    $this->maxFileSize = $maxFileSize;
    $this->maxFiles = $maxFiles;

    if (!is_dir($this->logPath)) {
      mkdir($this->logPath, 0755, true);
    }
  }

  public function log(string $level, string $message, array $context = []): void {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_SLASHES): '';

    $logEntry = sprintf(
      "[%s] %s: %s %s\n",
      $timestamp,
      strtoupper($level),
      $message,
      $contextStr
    );

    $logFile = $this->logPath . '/' . date('Y-m-d') . '.log';

    $this->rotateLogIfNeeded($logFile);

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
  }

  public function debug(string $message, array $context = []): void {
    $this->log(self::DEBUG, $message, $context);
  }

  public function info(string $message, array $context = []): void {
    $this->log(self::INFO, $message, $context);
  }

  public function warning(string $message, array $context = []): void {
    $this->log(self::WARNING, $message, $context);
  }

  public function error(string $message, array $context = []): void {
    $this->log(self::ERROR, $message, $context);
  }

  public function critical(string $message, array $context = []): void {
    $this->log(self::CRITICAL, $message, $context);
  }

  private function rotateLogIfNeeded(string $logFile): void {
    if (!file_exists($logFile) || filesize($logFile) < $this->maxFileSize) {
      return;
    }

    for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
      $oldFile = $logFile . '.' . $i;
      $newFile = $logFile . '.' . ($i + 1);

      if (file_exists($oldFile)) {
        unlink($oldFile);
      } else {
        rename($oldFile, $newFile);
      }
    }

    rename($logFile, $logFile . '.1');
  }
}
