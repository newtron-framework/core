<?php
declare(strict_types=1);

namespace Newtron\Core\Document;

use Newtron\Core\Application\App;

class AssetManager {
  protected array $scripts = [];
  protected array $stylesheets = [];
  protected array $usedScripts = [];
  protected array $usedStylesheets = [];

  /**
   * Register a script
   *
   * @param  string $name       Name used to identify the script
   * @param  string $uri        URI of the script file
   * @param  array  $attributes Attributes to set on the script tag
   */
  public function registerScript(string $name, string $uri, array $attributes = []): void {
    $this->scripts[$name] = [
      'src' => $uri,
      'attributes' => $attributes,
    ];
  }

  /**
   * Register a stylesheet
   *
   * @param  string $name       Name used to identify the stylesheet
   * @param  string $uri        URI of the stylesheet file
   */
  public function registerStylesheet(string $name, string $uri): void {
    $this->stylesheets[$name] = [
      'href' => $uri,
    ];
  }

  /**
   * Mark a registered script to be used in the document
   *
   * @param  string $name The script to use
   */
  public function useScript(string $name): void {
    if (!isset($this->scripts[$name])) {
      App::getLogger()->warning("No script '{$name}' registered");
      return;
    }

    $this->usedScripts[] = $name;
  }

  /**
   * Mark a registered stylesheet to be used in the document
   *
   * @param  string $name The stylesheet to use
   */
  public function useStylesheet(string $name): void {
    if (!isset($this->stylesheets[$name])) {
      App::getLogger()->warning("No stylesheet '{$name}' registered");
      return;
    }

    $this->usedStylesheets[] = $name;
  }

  /**
   * Get all used scripts
   *
   * @return array The used scripts
   */
  public function getScripts(): array {
    return array_filter($this->scripts, function ($name) {
      return in_array($name, $this->usedScripts);
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * Get all used stylesheets
   *
   * @return array The used stylesheets
   */
  public function getStylesheets(): array {
    return array_filter($this->stylesheets, function ($name) {
      return in_array($name, $this->usedStylesheets);
    }, ARRAY_FILTER_USE_KEY);
  }
}
