<?php
declare(strict_types=1);

namespace Tests\Unit\Container;

use Newtron\Core\Container\Container;
use Newtron\Core\Container\ServiceProviderRegistry;
use PHPUnit\Framework\TestCase;
use Tests\Mocks\Container\ServiceProvider;
use Tests\Mocks\Container\TestImplementation;
use Tests\Mocks\Container\TestInterface;

class ServiceProviderRegistryTest extends TestCase {
  private ServiceProviderRegistry $registry;
  private Container $container;

  public function setUp(): void {
    parent::setUp();
    $this->container = new Container();
    $this->registry = new ServiceProviderRegistry($this->container);
  }

  public function testRegisterProvider(): void {
    $this->registry->register(ServiceProvider::class);

    $reflection = new \ReflectionClass($this->registry);
    $property = $reflection->getProperty('providers');
    $property->setAccessible(true);
    $providers = $property->getValue($this->registry);
    
    $this->assertArrayHasKey(ServiceProvider::class, $providers);

    $instance = $this->container->get(TestInterface::class);

    $this->assertInstanceOf(TestImplementation::class, $instance);
  }

  public function testBoot(): void {
    $this->registry->register(ServiceProvider::class);
    $instance = $this->container->get(TestInterface::class);

    $this->assertEquals('default', $instance->value);

    $this->registry->boot();

    $this->assertEquals('boot_called', $instance->value);
  }
}
