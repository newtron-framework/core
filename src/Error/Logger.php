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

  /**
   * @param  string $logPath     The path for log files
   * @param  int    $maxFileSize The maximum size for a log file before creating another
   * @param  int    $maxFiles    The maximum number of log files before rotating
   */
  public function __construct(string $logPath = 'logs', int $maxFileSize = 10485760, int $maxFiles = 5) {
    $this->logPath = rtrim($logPath, '/');
    $this->maxFileSize = $maxFileSize;
    $this->maxFiles = $maxFiles;

    if (!is_dir($this->logPath)) {
      mkdir($this->logPath, 0755, true);
    }
  }

  /**
   * Add an entry to the application logs
   *
   * @param  string $level   The log level 
   * @param  string $message The log message
   * @param  array  $context Context for the log entry
   */
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

  /**
   * Log a debug message
   *
   * @param  string $message The log message
   * @param  array  $context Context for the log entry
   */
  public function debug(string $message, array $context = []): void {
    $this->log(self::DEBUG, $message, $context);
  }

  /**
   * Log an info message
   *
   * @param  string $message The log message
   * @param  array  $context Context for the log entry
   */
  public function info(string $message, array $context = []): void {
    $this->log(self::INFO, $message, $context);
  }

  /**
   * Log a warning message
   *
   * @param  string $message The log message
   * @param  array  $context Context for the log entry
   */
  public function warning(string $message, array $context = []): void {
    $this->log(self::WARNING, $message, $context);
  }

  /**
   * Log an error message
   *
   * @param  string $message The log message
   * @param  array  $context Context for the log entry
   */
  public function error(string $message, array $context = []): void {
    $this->log(self::ERROR, $message, $context);
  }

  /**
   * Log a critical message
   *
   * @param  string $message The log message
   * @param  array  $context Context for the log entry
   */
  public function critical(string $message, array $context = []): void {
    $this->log(self::CRITICAL, $message, $context);
  }

  /**
   * Rotate log files to prevent massive file build up
   *
   * @param  string $logFile The current log file
   */
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
