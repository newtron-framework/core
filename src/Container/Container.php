<?php
declare(strict_types=1);

namespace Newtron\Core\Container;

class Container {
  private array $services = [];
  private array $instances = [];
  private array $resolving = [];
  private array $reflectionCache = [];
  private array $dependencyCache = [];

  public function bind(string $abstract, $concrete = null, bool $singleton = false): void {
    if ($concrete === null) {
      $concrete = $abstract;
    }

    $this->services[$abstract] = [
      'concrete' => $concrete,
      'singleton' => $singleton,
    ];
  }

  public function singleton(string $abstract, $concrete = null): void {
    $this->bind($abstract, $concrete, true);
  }

  public function instance(string $abstract, object $instance): void {
    $this->instances[$abstract] = $instance;
  }

  public function get(string $abstract): object {
    if (isset($this->instances[$abstract])) {
      return $this->instances[$abstract];
    }

    if (isset($this->resolving[$abstract])) {
      throw new \RuntimeException("Circular dependency detected for '{$abstract}'");
    }

    $this->resolving[$abstract] = true;

    try {
      $instance = $this->resolve($abstract);

      if (isset($this->services[$abstract]['singleton']) && $this->services[$abstract]['singleton']) {
        $this->instances[$abstract] = $instance;
      }

      return $instance;
    } finally {
      unset($this->resolving[$abstract]);
    }
  }

  public function has(string $abstract): bool {
    return isset($this->services[$abstract]) || isset($this->instances[$abstract]) || class_exists($abstract);
  }

  private function resolve(string $abstract): object {
    if (isset($this->services[$abstract])) {
      $concrete = $this->services[$abstract]['concrete'];

      if (is_callable($concrete)) {
        return $concrete($this);
      }

      if (is_string($concrete)) {
        return $this->build($concrete);
      }

      throw new \InvalidArgumentException("Invalid service definition for '{$abstract}'");
    }

    if (class_exists($abstract)) {
      return $this->build($abstract);
    }

    throw new \RuntimeException("Service '{$abstract}' not found and cannot be resolved");
  }

  private function build(string $className): object {
    if (!isset($this->reflectionCache[$className])) {
      try {
        $reflector = new \ReflectionClass($className);
      } catch (\RuntimeException $e) {
        throw new \RuntimeException("Cannot reflect class '{$className}': " . $e->getMessage());
      }

      if (!$reflector->isInstantiable()) {
        throw new \RuntimeException("Class '{$className}' is not instantiable");
      }

      $this->reflectionCache[$className] = [
        'reflector' => $reflector,
        'constructor' => $reflector->getConstructor(),
      ];
    }

    $cached = $this->reflectionCache[$className];
    /** @var \ReflectionClass $reflector */
    /** @var ?\ReflectionMethod $constructor */
    $reflector = $cached['reflector'];
    $constructor = $cached['constructor'];

    if ($constructor === null) {
      return $reflector->newInstance();
    }

    $dependencies = $this->resolveDependencies($constructor, $className);

    return $reflector->newInstanceArgs($dependencies);
  }

  private function resolveDependencies(\ReflectionMethod $method, string $className): array {
    $cacheKey = "{$className}::{$method->getName()}";

    if (!isset($this->dependencyCache[$cacheKey])) {
      $dependencyMetadata = [];

      foreach ($method->getParameters() as $param) {
        $type = $param->getType();

        $metadata = [
          'name' => $param->getName(),
          'type' => null,
          'builtin' => false,
          'nullable' => $param->allowsNull(),
          'hasDefault' => $param->isDefaultValueAvailable(),
          'defaultValue' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
        ];

        if ($type !== null) {
          if ($type instanceof \ReflectionUnionType) {
            throw new \RuntimeException("Union types are not supported for parameter '{$param->getName()}'");
          }

          /** @var \ReflectionNamedType $type */
          $metadata['type'] = $type->getName();
          $metadata['builtin'] = $type->isBuiltin();
        }

        $dependencyMetadata[] = $metadata;
      }

      $this->dependencyCache[$cacheKey] = $dependencyMetadata;
    }

    $dependencies = [];

    foreach ($this->dependencyCache[$cacheKey] as $metadata) {
      if ($metadata['type'] === null) {
        if ($metadata['hasDefault']) {
          $dependencies[] = $metadata['defaultValue'];
          continue;
        }

        throw new \RuntimeException("Cannot resolve parameter '{$metadata['name']}' - no type hint provided");
      }

      if ($metadata['builtin']) {
        if ($metadata['hasDefault']) {
          $dependencies[] = $metadata['defaultValue'];
          continue;
        }

        throw new \RuntimeException("Cannot resolve built-in type '{$metadata['type']}' for parameter '{$metadata['name']}'");
      }

      try {
        $dependencies[] = $this->get($metadata['type']);
      } catch (\RuntimeException $e) {
        if ($metadata['nullable'] || $metadata['hasDefault']) {
          $dependencies[] = $metadata['hasDefault'] ? $metadata['defaultValue'] : null;
          continue;
        }

        throw new \RuntimeException("Cannot resolve dependency '{$metadata['type']}' for parameter '{$metadata['name']}': " . $e->getMessage());
      }
    }

    return $dependencies;
  }
}
