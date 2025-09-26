<?php
declare(strict_types=1);

namespace Tests;

use Newtron\Core\Quark\QuarkCompiler;
use Newtron\Core\Quark\QuarkEngine;

class QuarkTestCase extends BaseTestCase {
  protected string $testRootPath;
  protected string $testTemplatePath;
  protected string $testCachePath;
  protected QuarkEngine $engine;
  protected QuarkCompiler $compiler;

  public function setUp(): void {
    parent::setUp();
    $this->testRootPath = TEST_TEMP_DIR;
    $this->testTemplatePath = $this->testRootPath . '/templates';
    $this->testCachePath = $this->testRootPath . '/cache';

    if (!is_dir($this->testRootPath)) {
      mkdir($this->testRootPath, 0777, true);
    }
    mkdir($this->testTemplatePath, 0777, true);

    $this->engine = new QuarkEngine($this->testTemplatePath, $this->testCachePath);
    $this->compiler = $this->engine->getCompiler();
  }

  public function tearDown(): void {
    if (isset($this->testRootPath) && is_dir($this->testRootPath)) {
      $this->deleteDirectory($this->testRootPath);
    }

    parent::tearDown();
  }

  protected function createTestTemplate(string $path = 'test', string $content = '<div>{{ test }}</div>'): void {
    file_put_contents($this->testTemplatePath . "/{$path}.quark.html", $content);
  }
}
