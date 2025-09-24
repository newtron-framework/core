<?php
declare(strict_types=1);

namespace Newtron\Core\Quark;

class Quark {
  protected static ?QuarkEngine $engine = null;

  public static function setEngine(QuarkEngine $engine): void {
    static::$engine = $engine;
  }

  public static function render(string $template, array $data = [], array $outlets = []): string {
    return static::$engine->render($template, $data, $outlets);
  }

  public static function skipRootLayout(): void {
    static::$engine->skipRootLayout();
  }
}
