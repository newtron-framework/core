<?php
declare(strict_types=1);

namespace Newtron\Core\Quark;

class Quark {
  protected static ?QuarkEngine $engine = null;

  /**
   * Set the engine instance for the helper
   *
   * @param  QuarkEngine $engine 
   */
  public static function setEngine(QuarkEngine $engine): void {
    static::$engine = $engine;
  }

  /**
   * Render a template
   *
   * @param  string $template The name of the template to render
   * @param  array  $data     Data to make available to the template
   * @param  array  $outlets  Outlet content for the current render
   * @return string The rendered template
   */
  public static function render(string $template, array $data = [], array $outlets = []): string {
    return static::$engine->render($template, $data, $outlets);
  }

  /**
   * Skip rendering the root layout
   */
  public static function skipRootLayout(): void {
    static::$engine->skipRootLayout();
  }
}
