<?php
declare(strict_types=1);

namespace Tests;

use Newtron\Core\Routing\AbstractRouter;

class RoutingTestCase extends BaseTestCase {
  protected string $testRootPath;
  protected string $testRoutePath;
  protected AbstractRouter $router;

  public function setUp(): void {
    parent::setUp();
    $this->testRootPath = TEST_TEMP_DIR;
    $this->testRoutePath = $this->testRootPath . '/routes';

    if (!is_dir($this->testRootPath)) {
      mkdir($this->testRootPath, 0777, true);
    }
    mkdir($this->testRoutePath, 0777, true);
  }

  public function tearDown(): void {
    if (isset($this->testRootPath) && is_dir($this->testRootPath)) {
      $this->deleteDirectory($this->testRootPath);
    }

    parent::tearDown();
  }
}
