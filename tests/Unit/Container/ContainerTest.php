<?php
declare(strict_types=1);

namespace Tests\Unit\Container;

use Newtron\Core\Container\Container;
use PHPUnit\Framework\TestCase;
use Tests\Mocks\Container\CallableTestClass;
use Tests\Mocks\Container\CircularDependencyA;
use Tests\Mocks\Container\CircularDependencyB;
use Tests\Mocks\Container\ClassWithBuiltinDependencies;
use Tests\Mocks\Container\ClassWithDependencies;
use Tests\Mocks\Container\ClassWithOptionalDependencies;
use Tests\Mocks\Container\ClassWithUnionTypes;
use Tests\Mocks\Container\NonInstantiableClass;
use Tests\Mocks\Container\SimpleClass;
use Tests\Mocks\Container\TestImplementation;
use Tests\Mocks\Container\TestInterface;

class ContainerTest extends TestCase {
  private Container $container;

  public function setUp(): void {
    parent::setUp();
    $this->container = new Container();
  }

  public function testBindSimpleClass(): void {
    $this->container->bind(SimpleClass::class);

    $instance = $this->container->get(SimpleClass::class);

    $this->assertInstanceOf(SimpleClass::class, $instance);
  }

  public function testBindWithConcrete(): void {
    $this->container->bind(TestInterface::class, TestImplementation::class);

    $instance = $this->container->get(TestInterface::class);

    $this->assertInstanceOf(TestImplementation::class, $instance);
  }

  public function testBindWithClosure(): void {
    $this->container->bind(SimpleClass::class, function (Container $c) {
      $instance = new SimpleClass();
      $instance->value = 'from_closure';
      return $instance;
    });

    $instance = $this->container->get(SimpleClass::class);

    $this->assertInstanceOf(SimpleClass::class, $instance);
    $this->assertEquals('from_closure', $instance->value);
  }

  public function testBindNonSingleton(): void {
    $this->container->bind(SimpleClass::class);

    $instance1 = $this->container->get(SimpleClass::class);
    $instance2 = $this->container->get(SimpleClass::class);

    $this->assertInstanceOf(SimpleClass::class, $instance1);
    $this->assertInstanceOf(SimpleClass::class, $instance2);
    $this->assertNotSame($instance1, $instance2);
  }

  public function testBindSingleton(): void {
    $this->container->singleton(SimpleClass::class);

    $instance1 = $this->container->get(SimpleClass::class);
    $instance2 = $this->container->get(SimpleClass::class);

    $this->assertSame($instance1, $instance2);
  }

  public function testBindSingletonWithConcrete(): void {
    $this->container->singleton(TestInterface::class, TestImplementation::class);

    $instance1 = $this->container->get(TestInterface::class);
    $instance2 = $this->container->get(TestInterface::class);

    $this->assertSame($instance1, $instance2);
    $this->assertInstanceOf(TestImplementation::class, $instance1);
  }

  public function testBindSingletonWithClosure(): void {
    $this->container->singleton(SimpleClass::class, function (Container $c) {
      $instance = new SimpleClass();
      $instance->value = 'singleton_closure';
      return $instance;
    });
    
    $instance1 = $this->container->get(SimpleClass::class);
    $instance2 = $this->container->get(SimpleClass::class);
    
    $this->assertSame($instance1, $instance2);
    $this->assertEquals('singleton_closure', $instance1->value);
  }

  public function testBindInstance(): void {
    $existingInstance = new SimpleClass();
    $existingInstance->value = 'existing';

    $this->container->instance(SimpleClass::class, $existingInstance);

    $getInstance = $this->container->get(SimpleClass::class);

    $this->assertSame($existingInstance, $getInstance);
    $this->assertEquals('existing', $getInstance->value);
  }

  public function testBindInstanceOverridesSingleton(): void {
    $this->container->singleton(SimpleClass::class);
    $singletonInstance = $this->container->get(SimpleClass::class);
    
    $newInstance = new SimpleClass();
    $newInstance->value = 'override';
    $this->container->instance(SimpleClass::class, $newInstance);
    
    $retrievedInstance = $this->container->get(SimpleClass::class);
    
    $this->assertSame($newInstance, $retrievedInstance);
    $this->assertNotSame($singletonInstance, $retrievedInstance);
    $this->assertEquals('override', $retrievedInstance->value);
  }

  public function testResolveDependencies(): void {
    $this->container->bind(SimpleClass::class);
    $this->container->bind(ClassWithDependencies::class);

    $instance = $this->container->get(ClassWithDependencies::class);

    $this->assertInstanceOf(ClassWithDependencies::class, $instance);
    $this->assertInstanceOf(SimpleClass::class, $instance->dependency);
  }

  public function testResolveOptionalDependencies(): void {
    $this->container->bind(SimpleClass::class);
    $this->container->bind(TestImplementation::class);
    
    $instance = $this->container->get(ClassWithOptionalDependencies::class);
    
    $this->assertInstanceOf(ClassWithOptionalDependencies::class, $instance);
    $this->assertInstanceOf(SimpleClass::class, $instance->required);
    $this->assertInstanceOf(TestImplementation::class, $instance->optional);
  }

  public function testResolveBuiltinDependencies(): void {
    $instance = $this->container->get(ClassWithBuiltinDependencies::class);

    $this->assertInstanceOf(ClassWithBuiltinDependencies::class, $instance);
    $this->assertEquals('default', $instance->stringParam);
    $this->assertEquals(100, $instance->intParam);
  }

  public function testHasBoundService(): void {
    $this->container->bind(SimpleClass::class);

    $this->assertTrue($this->container->has(SimpleClass::class));
  }

  public function testCallClosure(): void {
    $this->container->bind(SimpleClass::class);

    $result = $this->container->call(function (SimpleClass $simple) {
      return $simple::class;
    });

    $this->assertEquals(SimpleClass::class, $result);
  }

  public function testCallArrayCallback(): void {
    $this->container->bind(SimpleClass::class);
    $this->container->bind(CallableTestClass::class);

    $result = $this->container->call([CallableTestClass::class, 'testMethod']);

    $this->assertEquals('method_called', $result);
  }

  public function testCallWithParameters(): void {
    $result = $this->container->call(function (string $name, int $count = 25) {
      return "Name: {$name}, Count: {$count}";
    }, ['name' => 'Test', 'count' => 25]);

    $this->assertEquals('Name: Test, Count: 25', $result);
  }

  public function testCircularDependencyDetection(): void {
    $this->container->bind(CircularDependencyA::class);
    $this->container->bind(CircularDependencyB::class);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Circular dependency detected');

    $this->container->get(CircularDependencyA::class);
  }

  public function testNonInstantiableClass(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('is not instantiable');

    $this->container->get(NonInstantiableClass::class);
  }

  public function testUnresolvableService(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Service \'NonExistentService\' not found');

    $this->container->get('NonExistentService');
  }

  public function testInvalidServiceDefinition(): void {
    $this->container->bind(SimpleClass::class, 12345);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid service definition');

    $this->container->get(SimpleClass::class);
  }

  public function testUnresolvableParameter(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Cannot resolve method dependency');

    $this->container->call(function ($unknownParam) {
      return $unknownParam;
    });
  }

  public function testUnresolvableBuiltInType(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Cannot resolve method dependency');
    
    $this->container->call(function (string $requiredString) {
      return $requiredString;
    });
  }

  public function testNonCallableCallback(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Callback is not callable');
    
    $this->container->call('not_a_callable_string');
  }

  public function testUnionTypesNotSupported(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Union types are not supported');
    
    $this->container->get(ClassWithUnionTypes::class);
  }

  public function testReflectionCaching(): void {
    $instance1 = $this->container->get(ClassWithDependencies::class);
    $instance2 = $this->container->get(ClassWithDependencies::class);
    
    $this->assertInstanceOf(ClassWithDependencies::class, $instance1);
    $this->assertInstanceOf(ClassWithDependencies::class, $instance2);
    
    $reflection = new \ReflectionClass($this->container);
    $cacheProperty = $reflection->getProperty('reflectionCache');
    $cacheProperty->setAccessible(true);
    $cache = $cacheProperty->getValue($this->container);
    
    $this->assertArrayHasKey(ClassWithDependencies::class, $cache);
  }

  public function testDependencyCaching(): void {
    $this->container->get(ClassWithDependencies::class);
    $this->container->get(ClassWithDependencies::class);
    
    $reflection = new \ReflectionClass($this->container);
    $cacheProperty = $reflection->getProperty('dependencyCache');
    $cacheProperty->setAccessible(true);
    $cache = $cacheProperty->getValue($this->container);
    
    $expectedKey = ClassWithDependencies::class . '::__construct';
    $this->assertArrayHasKey($expectedKey, $cache);
  }

  public function testResolveClassWithoutConstructor(): void {
    $instance = $this->container->get(SimpleClass::class);
    
    $this->assertInstanceOf(SimpleClass::class, $instance);
  }

  public function testBindSameAbstractMultipleTimes(): void {
    $this->container->bind(SimpleClass::class, function () {
      $instance = new SimpleClass();
      $instance->value = 'first';
      return $instance;
    });
    
    $this->container->bind(SimpleClass::class, function () {
      $instance = new SimpleClass();
      $instance->value = 'second';
      return $instance;
    });
    
    $instance = $this->container->get(SimpleClass::class);
    
    $this->assertEquals('second', $instance->value);
  }

  public function testGetSingletonAfterRegularBinding(): void {
    $this->container->bind(SimpleClass::class);
    $instance1 = $this->container->get(SimpleClass::class);
    
    $this->container->singleton(SimpleClass::class);
    $instance2 = $this->container->get(SimpleClass::class);
    $instance3 = $this->container->get(SimpleClass::class);
    
    $this->assertNotSame($instance1, $instance2);
    $this->assertSame($instance2, $instance3);
  }
}
