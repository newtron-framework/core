<?php
declare(strict_types=1);

namespace Newtron\Core\Quark;

class QuarkEngine {
  private QuarkCompiler $compiler;
  private string $templateDir;
  private string $cacheDir;
  private bool $debug;

  private ?string $rootLayout = null;
  private bool $skipRootLayout = false;
  private array $globals = [];
  private array $filters = [];
  private array $layouts = [];
  private array $outlets = [];
  private array $slots = [];
  private string $currentSlot = '';

  public function __construct(string $templateDir, string $cacheDir, bool $debug = false) {
    $this->templateDir = rtrim($templateDir, '/');
    $this->cacheDir = $cacheDir;
    $this->debug = $debug;

    $this->compiler = new QuarkCompiler($debug);

    if (!is_dir($this->cacheDir)) {
      mkdir($this->cacheDir, 0755, true);
    }

    $this->registerBuiltinFilters();
  }

  /**
   * Get the Quark compiler
   *
   * @return QuarkCompiler
   */
  public function getCompiler(): QuarkCompiler {
    return $this->compiler;
  }

  /**
   * Set the template to use as the root layout
   *
   * @param  string $layout The template to use
   */
  public function setRootLayout(string $layout): void {
    $this->rootLayout = $layout;
  }

  /**
   * Skip rendering the root layout
   */
  public function skipRootLayout(): void {
    $this->skipRootLayout = true;
  }

  /**
   * Render a template
   *
   * @param  string $template The name of the template to render
   * @param  array  $data     Data to make available to the template
   * @param  array  $outlets  Outlet content for the current render
   * @param  bool   $isNested Whether this is a nested render call
   * @return string The rendered template
   */
  public function render(string $template, array $data = [], array $outlets = [], bool $isNested = false): string {
    $templatePath = $this->templateDir . '/' . ltrim(str_replace('.', '/', $template), '/') . '.quark.html';

    if (!file_exists($templatePath)) {
      throw new \Exception("Template '{$template}' not found");
    }

    $this->skipRootLayout = false;
    $this->layouts = [];
    $this->outlets = $outlets;
    $this->currentSlot = '';

    $compiled = $this->compile($templatePath);

    $renderData = array_merge($this->globals, $data);
    $renderData['__quark'] = $this;

    ob_start();
    (function ($__data, $__template) {
      extract($__data);
      include $__template;
    })($renderData, $compiled);

    $content = ob_get_clean();

    if (!empty($this->layouts)) {
      $layout = array_pop($this->layouts);
      $this->outlets['default'] = $content;
      $result = $this->render($layout, $data, $this->outlets, true);

      if ($this->rootLayout && !$isNested && !$this->skipRootLayout) {
        $this->outlets['default'] = $result;
        return $this->render($this->rootLayout, $data, $this->outlets, true);
      }

      return $result;
    }
    
    if ($this->rootLayout && !$isNested && !$this->skipRootLayout) {
      $this->outlets['default'] = $content;
      return $this->render($this->rootLayout, $data, $this->outlets, true);
    }

    return $content;
  }

  /**
   * Set the layout for the current template
   *
   * @param  string $layout The template to use as the layout
   */
  public function setLayout(string $layout): void {
    $this->layouts[] = $layout;
  }

  /**
   * Render an outlet's content
   *
   * @param  string $name The outlet name
   * @param  array  $data Data to make available in the render
   * @return string The rendered outlet content
   */
  public function renderOutlet(string $name = 'default', array $data = []): string {
    if (isset($this->slots[$name])) {
      return $this->slots[$name];
    }

    if (isset($this->outlets[$name])) {
      if (is_string($this->outlets[$name])) {
        return $this->outlets[$name];
      } elseif (is_array($this->outlets[$name])) {
        $template = $this->outlets[$name]['template'];
        $outletData = array_merge($data, $this->outlets[$name]['data'] ?? []);
        return $this->render($template, $outletData);
      }
    }

    return '';
  }

  /**
   * Start a slot block
   *
   * @param  string $name The slot name
   */
  public function startSlot(string $name): void {
    $this->currentSlot = $name;
    ob_start();
  }

  /**
   * End the current slot block
   */
  public function endSlot(): void {
    if ($this->currentSlot) {
      $content = ob_get_clean();
      $this->slots[$this->currentSlot] = $content;
      $this->currentSlot = '';
    }
  }

  /**
   * Get the content for a slot
   *
   * @param  string $name    The slot name
   * @param  string $default The content to use if the slot is not set
   * @return string The slot content
   */
  public function getSlot(string $name, string $default = ''): string {
    return $this->slots[$name] ?? $default;
  }

  /**
   * Escape a value for rendering
   *
   * @param  mixed  $value   The value to escape
   * @param  string $context The context to use for escaping
   * @return string The escaped string
   */
  public function escape(mixed $value, string $context = 'html'): string {
    if ($value === null || is_bool($value)) {
      return $value ? '1' : '';
    }

    switch ($context) {
      case 'html':
      case 'attr':
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
      case 'js':
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
      case 'css':
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', (string)$value);
      case 'url':
        return rawurlencode((string)$value);
      case 'raw':
        return (string)$value;
      default:
        return (string)$value;
    }
  }

  /**
   * Apply a filter to a value
   *
   * @param  string $name  The filter name
   * @param  mixed  $value The value to filter
   * @param  any    $args  Additional arguments for the filter
   * @return mixed The result of the filter
   */
  public function applyFilter(string $name, mixed $value, ...$args): mixed {
    if (!isset($this->filters[$name])) {
      throw new \Exception("Unknown filter: {$name}");
    }

    return call_user_func($this->filters[$name], $value, ...$args);
  }

  /**
   * Add a custom filter
   *
   * @param  string   $name     The filter name
   * @param  callable $callback The filter function
   */
  public function addFilter(string $name, callable $callback): void {
    $this->filters[$name] = $callback;
  }

  /**
   * Make a value globally available in templates
   *
   * @param  string $name  Identifier for the value
   * @param  mixed  $value The value to make available
   */
  public function addGlobal(string $name, mixed $value): void {
    $this->globals[$name] = $value;
  }

  /**
   * Register the builtin Quark filters
   */
  private function registerBuiltinFilters(): void {
    $this->filters['upper'] = fn($v) => strtoupper($v);
    $this->filters['lower'] = fn($v) => strtolower($v);
    $this->filters['capitalize'] = fn($v) => ucfirst(strtolower($v));
    $this->filters['length'] = fn($v) => is_countable($v) ? count($v) : strlen($v);
    $this->filters['reverse'] = fn($v) => is_array($v) ? array_reverse($v) : strrev($v);
    $this->filters['sort'] = function($v) {
      if (is_array($v)) {
        sort($v);
        return $v;
      }
      return $v;
    };
    $this->filters['join'] = fn($v, $sep = ', ') => is_array($v) ? implode($sep, $v) : $v;
    $this->filters['default'] = fn($v, $default) => empty($v) ? $default : $v;
    $this->filters['date'] = fn($v, $format = 'Y-m-d') => date($format, is_numeric($v) ? $v : strtotime($v));
    $this->filters['truncate'] = fn($v, $len = 100, $suffix = '...') => 
      strlen($v) > $len ? substr($v, 0, $len) . $suffix : $v;
    $this->filters['raw'] = fn($v) => $this->escape($v, 'raw');
    $this->filters['json'] = fn($v) => $this->escape($v, 'js');
    $this->filters['dump'] = fn($v) => '<pre>' . $this->escape(print_r($v, true), 'html') . '</pre>';
  }

  /**
   * Compile a template
   * Results are cached and only recompiled when the file has been modified
   *
   * @param  string $templatePath The template file to compile
   * @return string The compiled template
   */
  private function compile(string $templatePath): string {
    $cacheKey = md5($templatePath);
    $cachePath = $this->cacheDir . '/' . $cacheKey . '.php';

    if (!$this->debug && 
      file_exists($cachePath) &&
      filemtime($cachePath) >= filemtime($templatePath)) {
      return $cachePath;
    }

    $source = file_get_contents($templatePath);
    $compiled = $this->compiler->compile($source);

    file_put_contents($cachePath, $compiled);
    return $cachePath;
  }
}
