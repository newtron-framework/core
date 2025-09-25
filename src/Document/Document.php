<?php
declare(strict_types=1);

namespace Newtron\Core\Document;

use Newtron\Core\Application\App;

class Document {
  protected AssetManager $assetManager;
  protected string $title;
  protected string $description;
  protected string $lang;
  protected array $meta = [];
  protected array $og = [];
  protected array $links = [];
  protected array $headScripts = [];
  protected array $postBodyScripts = [];
  protected array $inlineStyles = [];

  public function __construct(AssetManager $assetManager) {
    $this->assetManager = $assetManager;
    $this->title = App::getConfig()->get('app.name', 'Newtron');
    $this->description = '';
    $this->lang = App::getConfig()->get('app.language', 'en');
    $this->setFavicon('/favicon.ico');
  }

  /**
   * Get the document's asset manager
   *
   * @return AssetManager The asset manager
   */
  public function getAssetManager(): AssetManager {
    return $this->assetManager;
  }

  /**
   * Get the document title
   *
   * @return string The document title
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * Set the document title
   *
   * @param  string $title The title to set
   * @return Document The document instance for chaining
   */
  public function setTitle(string $title): self {
    $this->title = $title;
    return $this;
  }

  /**
   * Get the document description
   *
   * @return string The document description
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * Set the document description
   *
   * @param  string $description The description to set
   * @return Document The document instance for chaining
   */
  public function setDescription(string $description): self {
    $this->description = $description;
    return $this;
  }

  /**
   * Get the document language
   *
   * @return string The document language
   */
  public function getLang(): string {
    return $this->lang;
  }

  /**
   * Set the document language
   *
   * @param  string $lang The language to set
   * @return Document The document instance for chaining
   */
  public function setLang(string $lang): self {
    $this->lang = $lang;
    return $this;
  }

  /**
   * Get the document meta, or a specific meta tag
   *
   * @param  ?string  $name      If set, the tag name to get
   * @param  string   $attribute The attribute to use for $name (default 'name')
   * @return mixed If $name is set, the matching meta tag or null if it does not exist. The array of meta tags otherwise
   */
  public function getMeta(?string $name = null, string $attribute = 'name'): mixed {
    if (!$name) {
      return $this->meta;
    }

    return $this->meta[$attribute][$name] ?? null;
  }

  /**
   * Set a meta tag
   *
   * @param  string $name      The name of the tag to set
   * @param  string $content   The content to set
   * @param  string $attribute The attribute to use for $name (default 'name')
   * @return Document The document instance for chaining
   */
  public function setMeta(string $name, string $content, string $attribute = 'name'): self {
    $this->meta[$attribute][$name] = $content;
    return $this;
  }

  /**
   * Get the document's Open Graph properties, or a specific Open Graph property
   *
   * @param  ?string $property If set, the property to get
   * @return mixed If $property is set, the matching Open Graph property or null if it does not exist. The array of Open Graph properties otherwise
   */
  public function getOG(?string $property = null): mixed {
    if (!$property) {
      return $this->og;
    }

    if (!str_starts_with($property, 'og:')) {
      $property = 'og:' . $property;
    }
    return $this->og[$property] ?? null;
  }

  /**
   * Set an Open Graph property
   *
   * @param  string $property The property to set
   * @param  string $content  The content to set
   * @return Document The document instance for chaining
   */
  public function setOG(string $property, string $content): self {
    if (!str_starts_with($property, 'og:')) {
      $property = 'og:' . $property;
    }
    $this->og[$property] = $content;
    return $this;
  }

  /**
   * Get the document's link tags
   *
   * @return array Array of link tags
   */
  public function getLinks(): array {
    return $this->links;
  }

  /**
   * Add a link to the document head
   *
   * @param  string $href       The link href
   * @param  string $rel        The relation
   * @param  array  $attributes Additional attributes to set on the tag
   * @return Document The document instance for chaining
   */
  public function addLink(string $href, string $rel, array $attributes = []): self {
    $this->links[$href] = [
      'rel' => $rel,
      'attributes' => $attributes,
    ];
    return $this;
  }

  /**
   * Set the document favicon
   *
   * @param  string $path The path to the favicon file, relative to the app root
   * @return Document The document instance for chaining
   */
  public function setFavicon(string $path): self {
    $this->addLink($path, 'icon', ['type' => 'image/x-icon']);
    return $this;
  }

  /**
   * Add a stylesheet to the document
   *
   * @param  string $href       The stylesheet href
   * @param  array  $attributes Additional attributes to set on the tag
   * @return Document The document instance for chaining
   */
  public function addStylesheet(string $href, array $attributes = []): self {
    $this->addLink($href, 'stylesheet', $attributes);
  }

  /**
   * Get all inline styles added to the document
   *
   * @return array Array of the document's inline styles
   */
  public function getInlineStyles(): array {
    return $this->inlineStyles;
  }

  /**
   * Add an inline style to the document
   *
   * @param  string $content The CSS to set in the style tag
   * @return Document The document instance for chaining
   */
  public function addInlineStyle(string $content): self {
    $this->inlineStyles[] = [
      'content' => $content,
    ];
    return $this;
  }

  /**
   * Get all scripts added to the document head
   *
   * @return array Array of scripts added to the head
   */
  public function getHeadScripts(): array {
    return $this->headScripts;
  }

  /**
   * Get all scripts added to the end of the document body
   *
   * @return array Array of scripts added to the body
   */
  public function getBodyScripts(): array {
    return $this->postBodyScripts;
  }

  /**
   * Add a script to the document
   *
   * @param  string $src        The script src 
   * @param  array  $attributes Additional attributes to set on the tag
   * @param  bool   $inBody     Whether the script should be placed at the end of the body instead of in the document head
   * @return Document The document instance for chaining
   */
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

  /**
   * Add an inline script to the document
   *
   * @param  string $content    The JavaScript to set in the script tag
   * @param  array  $attributes Additional attributes to set on the tag
   * @param  bool   $inBody     Whether the script should be placed at the end of the body instead of in the document head
   * @return Document The document instance for chaining
   */
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

  /**
   * Convert an array of HTML attribute values to a string
   *
   * @param  array $attributes The attributes as $name => $value
   * @return string The formatted attribute string
   */
  public function implodeAttributes(array $attributes): string {
    $attrString = [];
    foreach ($attributes as $name => $value) {
      $attrString[] = "{$name}=\"{$value}\"";
    }

    return implode(' ', $attrString);
  }

  /**
   * Render the document head
   *
   * @return string The inner HTML for the document head
   */
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

    foreach ($this->assetManager->getStylesheets() as $_ => $style) {
      $html .= "<link href=\"{$style['href']}\" rel=\"stylesheet\">\n";
    }

    foreach ($this->links as $href => $opts) {
      $html .= "<link href=\"{$href}\" rel=\"{$opts['rel']}\" {$this->implodeAttributes($opts['attributes'])}>\n";
    }

    foreach ($this->assetManager->getScripts() as $_ => $script) {
      $html .= "<script src=\"{$script['src']}\" {$this->implodeAttributes($script['attributes'])}></script>\n";
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

  /**
   * Render the scripts for the end of the document body
   *
   * @return string The HTML for the scripts
   */
  public function renderBodyScripts(): string {
    $html = '';

    foreach ($this->postBodyScripts as $script) {
      if ($script['inline']) {
        $html .= "<script {$this->implodeAttributes($script['attributes'])}>{$script['content']}</script>\n";
      } else {
        $html .= "<script src=\"{$script['src']}\" {$this->implodeAttributes($script['attributes'])}></script>\n";
      }
    }

    return $html;
  }
}
