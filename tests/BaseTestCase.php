<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase {
  protected function deleteDirectory(string $dir): void {
    if (!is_dir($dir)) {
      return;
    }

    $files = scandir($dir);
    foreach ($files as $file) {
      if ($file === '.' || $file === '..') {
        continue;
      }

      $path = $dir . '/' . $file;
      is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
  }
}
