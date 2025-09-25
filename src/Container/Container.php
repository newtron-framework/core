<?php
declare(strict_types=1);

namespace Newtron\Core\Container;

class Container {
  private array $services = [];
  private array $instances = [];
  private array $resolving = [];
  private array $reflectionCache = [];
  private array $dependencyCache = [];

  /**
   * Create a binding for a service
   *
   * @param  string $abstract  The abstract type or interface name
   * @param  mixed  $concrete  The concrete implementation (class name, closure)
   * @param  bool   $singleton Whether the binding should be treated as a singleton
   */
  public function bind(string $abstract, $concrete = null, bool $singleton = false): void {
    if ($concrete === null) {
      $concrete = $abstract;
    }

    $this->services[$abstract] = [
      'concrete' => $concrete,
      'singleton' => $singleton,
    ];
  }

  /**
   * Create a singleton binding
   *
   * @param  string $abstract The abstract type or interface name
   * @param  mixed  $concrete The concrete implementation (class name, closure)
   */
  public function singleton(string $abstract, $concrete = null): void {
    $this->bind($abstract, $concrete, true);
  }

  /**
   * Register an existing instance as a singleton
   *
   * @param  string $abstract The abstract type or interface name
   * @param  mixed  $instance The instance to register
   */
  public function instance(string $abstract, object $instance): void {
    $this->instances[$abstract] = $instance;
  }

  /**
   * Get the given type from the container
   *
   * @param  string $abstract The abstract type to resolve
   * @return mixed The resolved instance
   */
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

  /**
   * Check if an abstract type has been bound
   *
   * @param  string $abstract The abstract type to check
   * @return bool True if the type is bound, false otherwise
   */
  public function has(string $abstract): bool {
    return isset($this->services[$abstract]) || isset($this->instances[$abstract]) || class_exists($abstract);
  }

  /**
   * Call a method with dependency injection
   *
   * @param  callable|array|string  $callback   The callback to call
   * @param  array                  $parameters Additional parameters to pass to the method
   * @return mixed The result of the method call
   * @throws \Exception If the callback is not callable or dependencies cannot be resolved 
   */
  public function call(callable|array|string $callback, array $parameters = []): mixed {
    if (is_array($callback) && count($callback) === 2) {
      [$class, $method] = $callback;

      if (is_string($class)) {
        $class = $this->get($class);
      }

      $callback = [$class, $method];
    }

    if (!is_callable($callback)) {
      throw new \Exception('Callback is not callable');
    }

    return $this->callMethod($callback, $parameters);
  }

  /**
   * Call a method with dependency injection
   *
   * @param  callable  $callback   The callback to call
   * @param  array     $parameters Additional parameters to pass to the method
   * @return mixed The result of the method call
   */
  private function callMethod(callable $callback, array $parameters = []): mixed {
    if (is_array($callback)) {
      $reflector = new \ReflectionMethod($callback[0], $callback[1]);
    } else {
      $reflector = new \ReflectionFunction($callback);
    }

    $dependencies = $reflector->getParameters();

    if (empty($dependencies)) {
      return call_user_func($callback);
    }

    $resolvedDependencies = $this->resolveMethodDependencies($dependencies, $parameters);

    return call_user_func_array($callback, $resolvedDependencies);
  }

  /**
   * Resolve the given type from the container
   *
   * @param  string $abstract   The abstract type to resolve
   * @return mixed The resolved instance
   * @throws \InvalidArgumentException If the concrete definition is invalid
   * @throws \RuntimeException If the type cannot be resolved
   */
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

  /**
   * Create an instance of the given class
   *
   * @param  string $className  The class to build
   * @return object The built instance
   * @throws \RuntimeException If the target is not instantiable or cannot be reflected
   */
  private function build(string $className): object {
    if (!isset($this->reflectionCache[$className])) {
      try {
        $reflector = new \ReflectionClass($className);
      } catch (\Exception $e) {
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

  /**
   * Resolve all dependencies for a class method
   *
   * @param  \ReflectionMethod $method     The reflection method
   * @param  string            $className  The class name
   * @return array Array of resolved dependency instances
   * @throws \RuntimeException If a parameter is a union type (union types are not supported)
   */
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

  /**
   * Resolve all dependencies passed to a method, using passed parameters
   *
   * @param  array $dependencies The method dependencies
   * @param  array $parameters   The parameters that were passed to the method
   * @return array Array of resolved dependency instances
   */
  private function resolveMethodDependencies(array $dependencies, array $parameters = []): array {
    $results = [];

    foreach ($dependencies as $dependency) {
      if (array_key_exists($dependency->getName(), $parameters)) {
        $results[] = $parameters[$dependency->getName()];
        continue;
      }

      $type = $dependency->getType();

      if (is_null($type) || $type->isBuiltin()) {
        if ($dependency->isDefaultValueAvailable()) {
          $results[] = $dependency->getDefaultValue();
        } else {
          throw new \Exception("Cannot resolve method dependency: {$dependency->getName()}");
        }
      } else {
        $results[] = $this->get($type->getName());
      }
    }

    return $results;
  }
}
