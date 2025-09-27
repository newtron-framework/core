<?php
declare(strict_types=1);

namespace Newtron\Core\Application;

class Config {
  protected array $items = [];

  /**
   * @param  array $items Items in the config
   */
  public function __construct(array $items = []) {
    $this->items = $items;
  }

  /**
   * Check if the config contains the given item
   *
   * @param  string $key The config item key in dot notation
   * @return bool True if the config has the key, false otherwise
   */
  public function has(string $key): bool {
    return $this->get($key) !== null;
  }

  /**
   * Get a config value
   *
   * @param  string $key     The config item key in dot notation 
   * @param  mixed  $default The default value to use if the item is not set
   * @return mixed The item value, or $default if the item is not set 
   */
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

  /**
   * Set a config value
   *
   * @param  string $key   The config item key in dot notation
   * @param  mixed  $value The value to set
   * @throws \InvalidArgumentException If the config file for the given key is not found
   */
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
    file_put_contents($filePath, "<?php\n\nreturn " . $this->serial($filename) . ";");
  }

  /**
   * Get all config items
   *
   * @return array The array of config items
   */
  public function all(): array {
    return $this->items;
  }

  /**
   * Normalize the stored representation of a config section
   *
   * @param  string $section The config section to normalize
   * @return string The normalized string version of the section
   */
  protected function serial(string $section): string {
    $output = var_export($this->items[$section], true);
    preg_match('/array \((.*)\)/s', $output, $matches);
    $output = '[' . $matches[1] . ']';

    return $output;
  }
}
