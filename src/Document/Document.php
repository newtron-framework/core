<?php
declare(strict_types=1);

namespace Newtron\Core\Document;

use Newtron\Core\Application\App;

class Document {
  protected string $title;
  protected string $description;
  protected string $lang;
  protected array $meta = [];
  protected array $og = [];
  protected array $links = [];
  protected array $headScripts = [];
  protected array $postBodyScripts = [];
  protected array $inlineStyles = [];

  public function __construct() {
    $this->title = App::getConfig()->get('app.name', 'Newtron');
    $this->description = '';
    $this->lang = App::getConfig()->get('app.language', 'en');
    $this->setFavicon('/favicon.ico');
  }

  public function getTitle(): string {
    return $this->title;
  }

  public function setTitle(string $title): self {
    $this->title = $title;
    return $this;
  }

  public function getDescription(): string {
    return $this->description;
  }

  public function setDescription(string $description): self {
    $this->description = $description;
    return $this;
  }

  public function getLang(): string {
    return $this->lang;
  }

  public function setLang(string $lang): self {
    $this->lang = $lang;
    return $this;
  }

  public function getMeta(?string $name = null, string $attribute = 'name'): mixed {
    if (!$name) {
      return $this->meta;
    }

    return $this->meta[$attribute][$name] ?? null;
  }

  public function setMeta(string $name, string $content, string $attribute = 'name'): self {
    $this->meta[$attribute][$name] = $content;
    return $this;
  }

  public function getOG(?string $property = null): mixed {
    if (!$property) {
      return $this->og;
    }

    if (!str_starts_with($property, 'og:')) {
      $property = 'og:' . $property;
    }
    return $this->og[$property] ?? null;
  }

  public function setOG(string $property, string $content): self {
    if (!str_starts_with($property, 'og:')) {
      $property = 'og:' . $property;
    }
    $this->og[$property] = $content;
    return $this;
  }

  public function getLinks(): array {
    return $this->links;
  }

  public function addLink(string $href, string $rel, array $attributes = []): self {
    $this->links[$href] = [
      'rel' => $rel,
      'attributes' => $attributes,
    ];
    return $this;
  }

  public function setFavicon(string $path): self {
    $this->addLink($path, 'icon', ['type' => 'image/x-icon']);
    return $this;
  }

  public function addStylesheet(string $href, array $attributes = []): self {
    $this->addLink($href, 'stylesheet', $attributes);
  }

  public function getInlineStyles(): array {
    return $this->inlineStyles;
  }

  public function addInlineStyle(string $content): self {
    $this->inlineStyles[] = [
      'content' => $content,
    ];
    return $this;
  }

  public function getHeadScripts(): array {
    return $this->headScripts;
  }

  public function getBodyScripts(): array {
    return $this->postBodyScripts;
  }

  public function addScript(string $src, array $attributes = [], bool $inBody = false): self {
    $scriptData = [
      'inline' => false,
      'src' => $src,
      'attributes' => $attributes,
    ];

    if ($inBody) {
      $this->postBodyScripts[] = $scriptData;
    } else {
      $this->headScripts[] = $scriptData;
    }

    return $this;
  }

  public function addInlineScript(string $content, array $attributes = [], bool $inBody = false): self {
    $scriptData = [
      'inline' => true,
      'content' => $content,
      'attributes' => $attributes,
    ];

    if ($inBody) {
      $this->postBodyScripts[] = $scriptData;
    } else {
      $this->headScripts[] = $scriptData;
    }

    return $this;
  }

  public function implodeAttributes(array $attributes): string {
    $attrString = [];
    foreach ($attributes as $name => $value) {
      $attrString[] = "{$name}=\"{$value}\"";
    }

    return implode(' ', $attrString);
  }

  public function renderHead(): string {
    $html = '';

    $html .= "<title>{$this->title}</title>\n";

    $html .= "<meta name=\"description\" content=\"{$this->description}\">\n";

    foreach ($this->meta as $attribute => $names) {
      foreach ($names as $name => $content) {
        $html .= "<meta {$attribute}=\"{$name}\" content=\"{$content}\">\n";
      }
    }

    foreach ($this->og as $property => $content) {
      $html .= "<meta property=\"{$property}\" content=\"{$content}\">\n";
    }

    foreach ($this->links as $href => $opts) {
      $html .= "<link href=\"{$href}\" rel=\"{$opts['rel']}\" {$this->implodeAttributes($opts['attributes'])}>\n";
    }

    foreach ($this->headScripts as $script) {
      if ($script['inline']) {
        $html .= "<script {$this->implodeAttributes($script['attributes'])}>{$script['content']}</script>\n";
      } else {
        $html .= "<script src=\"{$script['src']}\" {$this->implodeAttributes($script['attributes'])}></script>\n";
      }
    }

    foreach ($this->inlineStyles as $style) {
      $html .= "<style>{$style['content']}</style>\n";
    }

    return $html;
  }
}
