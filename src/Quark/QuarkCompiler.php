<?php
declare(strict_types=1);

namespace Newtron\Core\Quark;

class QuarkCompiler {
  private bool $debug;
  private array $directives = [];

  public function __construct(bool $debug = false) {
    $this->debug = $debug;

    $this->registerBuiltinDirectives();
  }

  /**
   * Compile a template
   *
   * @param  string $source The template source
   * @return string The compiled template
   */
  public function compile(string $source): string {
    $tokens = $this->tokenize($source);
    $php = "<?php\n";

    foreach ($tokens as $token) {
      switch ($token['type']) {
        case 'text':
          $php .= 'echo ' . var_export($token['content'], true) . ";\n";
          break;
        case 'expression':
          $php .= $this->compileExpression($token['content']);
          break;
        case 'directive':
          $php .= $this->compileDirective($token['name'], $token['args']);
          break;
      }
    }

    return $php;
  }

  /**
   * Add a custom directive
   *
   * @param  string   $name     The directive name
   * @param  callable $compiler The directive function
   */
  public function addDirective(string $name, callable $compiler): void {
    $this->directives[$name] = $compiler;
  }

  /**
   * Register the builtin Quark directives
   */
  private function registerBuiltinDirectives(): void {
    $this->directives['layout'] = function ($args) {
      $template = trim($args, '"\'');
      return "\$__quark->setLayout('{$template}');\n";
    };
    
    $this->directives['skip_root'] = function ($args) {
      return "\$__quark->skipRootLayout();\n";
    };

    $this->directives['outlet'] = function ($args) {
      $name = $args ? trim($args, '"\'') : 'default';
      return "echo \$__quark->renderOutlet('{$name}', get_defined_vars());\n";
    };

    $this->directives['slot'] = function ($args) {
      return "\$__quark->startSlot('{$args}');\n";
    };

    $this->directives['endslot'] = function ($args) {
      return "\$__quark->endSlot();\n";
    };

    $this->directives['include'] = function ($args) {
      if (preg_match('/^["\']([^"\']+)["\'](?:\s*,\s*(.+))?$/', $args, $matches)) {
        $template = $matches[1];
        $data = $matches[2] ?? '[]';
        return "echo \$__quark->render('{$template}', array_merge(get_defined_vars(), {$data}));\n";
      }
      throw new \Exception("Invalid include syntax: {$args}");
    };

    $this->directives['if'] = fn($args) => "if ({$args}) {\n";
    $this->directives['elseif'] = fn($args) => "} elseif ({$args}) {\n";
    $this->directives['else'] = fn($args) => "} else {\n";
    $this->directives['endif'] = fn($args) => "}\n";
    $this->directives['foreach'] = fn($args) => "foreach ({$args}) {\n";
    $this->directives['endforeach'] = fn($args) => "}\n";
    
    $this->directives['set'] = function($args) {
      if (preg_match('/^(\$\w+)\s*=\s*(.+)$/', $args, $matches)) {
        return "{$matches[1]} = {$matches[2]};\n";
      }
      throw new \Exception("Invalid set syntax: {$args}");
    };
  }

  /**
   * Tokenize template source
   *
   * @param  string $source The template source
   * @return array The tokenized template
   */
  private function tokenize(string $source): array {
    $tokens = [];
    $pattern = '/\{\{(.*?)\}\}|\{%(.*?)%\}/s';
    $lastPos = 0;

    preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE);

    foreach ($matches[0] as $i => $match) {
      if ($match[1] > $lastPos) {
        $text = substr($source, $lastPos, $match[1] - $lastPos);
        if ($text !== '') {
          $tokens[] = ['type' => 'text', 'content' => $text];
        }
      }

      if (!empty($matches[1][$i][0])) {
        $tokens[] = ['type' => 'expression', 'content' => trim($matches[1][$i][0])];
      } elseif (!empty($matches[2][$i][0])) {
        $directive = trim($matches[2][$i][0]);
        $tokens[] = $this->parseDirective($directive);
      }

      $lastPos = $match[1] + strlen($match[0]);
    }

    if ($lastPos < strlen($source)) {
      $text = substr($source, $lastPos);
      if ($text !== '') {
        $tokens[] = ['type' => 'text', 'content' => $text];
      }
    }

    return $tokens;
  }

  /**
   * Parse a directive
   *
   * @param  string $directive The directive string
   * @return array The parsed directive
   * @throws \Exception If the directive syntax is invalid
   */
  private function parseDirective(string $directive): array {
    if (preg_match('/^(\w+)(?:\s+(.+))?$/', $directive, $matches)) {
      return [
        'type' => 'directive',
        'name' => $matches[1],
        'args' => $matches[2] ?? '',
      ];
    }

    throw new \Exception("Invalid directive syntax: {$directive}");
  }

  /**
   * Compile an expression
   *
   * @param  string $expression The expression string
   * @return string The compiled expression
   */
  private function compileExpression(string $expression): string {
    if (strpos($expression, '|') !== false) {
      return $this->compilePipeExpression($expression);
    }

    $variable = $this->normalizeVariable($expression);
    return "echo \$__quark->escape({$variable});\n";
  }

  /**
   * Compile an expression that uses pipes
   *
   * @param  string $expression The pipe expression string
   * @return string The compiled expression
   */
  private function compilePipeExpression(string $expression): string {
    $parts = array_map('trim', explode('|', $expression));
    $variable = array_shift($parts);

    $variable = $this->normalizeVariable($variable);

    $php = "\$__pipe_value = {$variable};\n";

    foreach ($parts as $filter) {
      if (preg_match('/^(\w+)(?:\((.*?)\))?$/', $filter, $matches)) {
        $filterName = $matches[1];
        $args = $matches[2] ?? '';

        if ($args) {
          $php .= "\$__pipe_value = \$__quark->applyFilter('{$filterName}', \$__pipe_value, {$args});\n";
        } else {
          $php .= "\$__pipe_value = \$__quark->applyFilter('{$filterName}', \$__pipe_value);\n";
        }
      }
    }

    $php .= "echo \$__pipe_value;\n";
    return $php;
  }

  /**
   * Normalize a PHP variable string
   *
   * @param  string $expression The expression to normalize
   * @return string The normalized string
   */
  private function normalizeVariable(string $expression): string {
    $expression = trim($expression);

    if (str_starts_with($expression, '$')) {
      return $expression;
    }

    if (strpos($expression, '.') !== false) {
      if (preg_match('/^(\w+)\.(.+)$/', $expression, $matches)) {
        return '$' . $matches[1] . '->' . str_replace('.', '->', $matches[2]);
      }
    }

    if (strpos($expression, '->') !== false) {
      if (preg_match('/^(\w+)->(.+)$/', $expression, $matches)) {
        return '$' . $matches[1] . '->' . str_replace('.', '->', $matches[2]);
      }
    }

    if (preg_match('/^\w+$/', $expression)) {
      return '$' . $expression;
    }

    return $expression;
  }

  /**
   * Compile a directive
   *
   * @param  string $name The directive name 
   * @param  string $args Arguments for the directive
   * @return string The directive result
   * @throws \Exception If the directive is unknown
   */
  private function compileDirective(string $name, string $args): string {
    if (isset($this->directives[$name])) {
      return call_user_func($this->directives[$name], $args);
    }

    throw new \Exception("Unknown directive: {$name}");
  }
}
