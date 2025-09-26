<?php
declare(strict_types=1);

namespace Tests\Unit\Application;

use Newtron\Core\Application\App;
use Newtron\Core\Application\Config;
use Tests\AppTestCase;

class ConfigTest extends AppTestCase {
  public function testConfigSetsValue(): void {
    $this->createTestConfig('app', ['name' => 'Test', 'debug' => true]);

    App::create($this->testRootPath);

    $config = App::getConfig();
    $this->assertInstanceOf(Config::class, $config);
    $config->set('app.new_value', 'test_value');
    $this->assertEquals('test_value', $config->get('app.new_value'));
    $this->assertEquals(
      [
        'name' => 'Test',
        'debug' => true,
        'new_value' => 'test_value',
      ],
      require $this->testConfigPath . '/app.php'
    );
  }
}
