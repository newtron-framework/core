<?php
declare(strict_types=1);

namespace Newtron\Core\Document;

use Newtron\Core\Application\App;

class AssetManager {
  protected array $scripts = [];
  protected array $stylesheets = [];
  protected array $usedScripts = [];
  protected array $usedStylesheets = [];

  public function registerScript(string $name, string $uri, array $attributes = []): void {
    $this->scripts[$name] = [
      'src' => $uri,
      'attributes' => $attributes,
    ];
  }

  public function registerStylesheet(string $name, string $uri): void {
    $this->stylesheets[$name] = [
      'href' => $uri,
    ];
  }

  public function useScript(string $name): void {
    if (!isset($this->scripts[$name])) {
      App::getLogger()->warning("No script '{$name}' registered");
      return;
    }

    $this->usedScripts[] = $name;
  }

  public function useStylesheet(string $name): void {
    if (!isset($this->stylesheets[$name])) {
      App::getLogger()->warning("No stylesheet '{$name}' registered");
      return;
    }

    $this->usedStylesheets[] = $name;
  }

  public function getScripts(): array {
    return array_filter($this->scripts, function ($name) {
      return in_array($name, $this->usedScripts);
    }, ARRAY_FILTER_USE_KEY);
  }

  public function getStylesheets(): array {
    return array_filter($this->stylesheets, function ($name) {
      return in_array($name, $this->usedStylesheets);
    }, ARRAY_FILTER_USE_KEY);
  }
}
