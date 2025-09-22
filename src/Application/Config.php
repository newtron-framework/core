<?php
declare(strict_types=1);

namespace Newtron\Core\Application;

class Config {
  protected array $items = [];

  public function __construct(array $items = []) {
    $this->items = $items;
  }

  public function has(string $key): bool {
    return $this->get($key) !== null;
  }

  public function get(string $key, mixed $default = null): mixed {
    $keys = explode('.', $key);
    $config = $this->items;

    foreach ($keys as $segment) {
      if (!is_array($config) || !array_key_exists($segment, $config)) {
        return $default;
      }
      $config = $config[$segment];
    }

    return $config;
  }

  public function set(string $key, mixed $value): void {
    $keys = explode('.', $key);
    $config = &$this->items;

    $filename = strtolower($keys[0]);
    $filePath = NEWTRON_CONFIG . '/' . $filename . '.php';

    if (!array_key_exists($filename, $config) || !file_exists($filePath)) {
      throw new \InvalidArgumentException("Config file '{$filename}' not found");
    }

    while (count($keys) > 1) {
      $segment = array_shift($keys);

      if (!isset($config[$segment]) || !is_array($config[$segment])) {
        $config[$segment] = [];
      }

      $config = &$config[$segment];
    }

    $config[array_shift($keys)] = $value;
    file_put_contents($filePath, '<?php\n\nreturn ' . $this->serial($config[$filename]));
  }

  public function all(): array {
    return $this->items;
  }

  protected function serial(string $section): string {
    $output = var_export($this->items[$section], true);
    preg_match('/array \((.*)\)/s', $output, $matches);
    $output = '[' . $matches[1] . ']';

    return $output;
  }
}
