<?php
declare(strict_types=1);

namespace Tests;

use Newtron\Core\Application\App;

class AppTestCase extends BaseTestCase {
  protected string $testRootPath;
  protected string $testConfigPath;
  protected string $testEnvPath;

  protected function setUp(): void {
    parent::setUp();

    $this->setupTestEnvironment();

    $this->resetAppInstance();
  }

  protected function tearDown(): void {
    if (isset($this->testRootPath) && is_dir($this->testRootPath)) {
      $this->deleteDirectory($this->testRootPath);
    }

    $this->resetAppInstance();

    restore_error_handler();
    restore_exception_handler();

    parent::tearDown();
  }

  protected function setupTestEnvironment(): void {
    $this->testRootPath = TEST_TEMP_DIR;
    $this->testConfigPath = $this->testRootPath . '/config';
    $this->testEnvPath = $this->testRootPath . '/.env';

    if (!is_dir($this->testRootPath)) {
      mkdir($this->testRootPath, 0777, true);
    }
    mkdir($this->testConfigPath, 0777, true);
    mkdir($this->testRootPath . '/cache', 0777, true);
    mkdir($this->testRootPath . '/logs', 0777, true);
    mkdir($this->testRootPath . '/routes', 0777, true);
    mkdir($this->testRootPath . '/templates', 0777, true);
  }

  protected function resetAppInstance(): void {
    $reflection = new \ReflectionClass(App::class);

    if ($reflection->hasProperty('instance')) {
      $instanceProperty = $reflection->getProperty('instance');
      $instanceProperty->setAccessible(true);
      $instanceProperty->setValue(null, null);
    }
    
    if ($reflection->hasProperty('globalMiddleware')) {
      $property = $reflection->getProperty('globalMiddleware');
      $property->setAccessible(true);
      $property->setValue(null, []);
    }
  }

  protected function createTestConfig(string $name, array $config): void {
    $content = '<?php return ' . var_export($config, true) . ';';
    file_put_contents($this->testConfigPath . '/' . $name . '.php', $content);
  }

  protected function createTestEnv(array $variables): void {
    $content = '';
    foreach ($variables as $key => $value) {
      $content .= "{$key}={$value}\n";
    }
    file_put_contents($this->testEnvPath, $content);
  }

  protected function createTestApp(array $config = []): App {
    $defaultConfig = [
      'app' => [
        'name' => 'Newtron',
        'debug' => false,
      ]
    ];
    $mergedConfig = array_merge($defaultConfig, $config);
    foreach ($mergedConfig as $section => $data) {
      $this->createTestConfig($section, $data);
    }

    return App::create($this->testRootPath);
  }
}
