<?php
declare(strict_types=1);

namespace Tests\Unit\Document;

use Newtron\Core\Document\AssetManager;
use PHPUnit\Framework\TestCase;

class AssetManagerTest extends TestCase {
  private AssetManager $am;

  public function setUp(): void {
    parent::setUp();
    $this->am = new AssetManager();
  }

  public function testRegisterScript(): void {
    $this->am->registerScript('test_script', 'test.js');

    $reflection = new \ReflectionClass($this->am);
    $property = $reflection->getProperty('scripts');
    $property->setAccessible(true);
    $scripts = $property->getValue($this->am);

    $this->assertArrayHasKey('test_script', $scripts);
    $this->assertEquals(['src' => 'test.js', 'attributes' => []], $scripts['test_script']);
  }

  public function testRegisterScriptWithAttributes(): void {
    $this->am->registerScript('test_script', 'test.js', ['type' => 'module']);

    $reflection = new \ReflectionClass($this->am);
    $property = $reflection->getProperty('scripts');
    $property->setAccessible(true);
    $scripts = $property->getValue($this->am);

    $this->assertArrayHasKey('test_script', $scripts);
    $this->assertEquals(
      ['src' => 'test.js', 'attributes' => ['type' => 'module']],
      $scripts['test_script']
    );
  }

  public function testRegisterStylesheet(): void {
    $this->am->registerStylesheet('test_style', 'test.css');

    $reflection = new \ReflectionClass($this->am);
    $property = $reflection->getProperty('stylesheets');
    $property->setAccessible(true);
    $styles = $property->getValue($this->am);

    $this->assertArrayHasKey('test_style', $styles);
    $this->assertEquals(['href' => 'test.css'], $styles['test_style']);
  }

  public function testUseScript(): void {
    $this->am->registerScript('test_script', 'test.js');
    $this->am->useScript('test_script');

    $reflection = new \ReflectionClass($this->am);
    $property = $reflection->getProperty('usedScripts');
    $property->setAccessible(true);
    $usedScripts = $property->getValue($this->am);

    $this->assertContains('test_script', $usedScripts);
  }

  public function testUseStylesheet(): void {
    $this->am->registerStylesheet('test_style', 'test.css');
    $this->am->useStylesheet('test_style');

    $reflection = new \ReflectionClass($this->am);
    $property = $reflection->getProperty('usedStylesheets');
    $property->setAccessible(true);
    $usedStyles = $property->getValue($this->am);

    $this->assertContains('test_style', $usedStyles);
  }

  public function testGetsOnlyUsedScripts(): void {
    $this->am->registerScript('used_script', 'used.js');
    $this->am->registerScript('unused_script', 'unused.js');
    $this->am->useScript('used_script');

    $scripts = $this->am->getScripts();

    $this->assertArrayHasKey('used_script', $scripts);
    $this->assertArrayNotHasKey('unused_script', $scripts);
  }

  public function testGetsOnlyUsedStylesheets(): void {
    $this->am->registerStylesheet('used_style', 'used.css');
    $this->am->registerStylesheet('unused_style', 'unused.css');
    $this->am->useStylesheet('used_style');

    $styles = $this->am->getStylesheets();

    $this->assertArrayHasKey('used_style', $styles);
    $this->assertArrayNotHasKey('unused_style', $styles);
  }
}
