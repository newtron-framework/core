<?php
declare(strict_types=1);

namespace Tests\Unit\Document;

use Newtron\Core\Application\App;
use Newtron\Core\Document\Document;
use Tests\AppTestCase as AppTestCase;

class DocumentTest extends AppTestCase {
  public function testDocumentInitializes(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();

    $this->assertInstanceOf(Document::class, $document);
  }

  public function testSetTitle(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setTitle('New Title');

    $this->assertEquals('New Title', $document->getTitle());
  }

  public function testSetDescription(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setDescription('New Description');

    $this->assertEquals('New Description', $document->getDescription());
  }

  public function testSetLang(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setLang('test');

    $this->assertEquals('test', $document->getLang());
  }

  public function testSetMeta(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setMeta('test', 'test_meta');

    $this->assertEquals(['name' => ['test' => 'test_meta']], $document->getMeta());
  }

  public function testSetMetaWithCustomAttribute(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setMeta('test', 'test_meta', 'custom');

    $this->assertEquals(['custom' => ['test' => 'test_meta']], $document->getMeta());
  }

  public function testGetAllMeta(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setMeta('first', 'first_meta');
    $document->setMeta('second', 'second_meta');

    $this->assertEquals(
      ['name' => ['first' => 'first_meta', 'second' => 'second_meta']],
      $document->getMeta()
    );
  }

  public function testGetSingleMeta(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setMeta('first', 'first_meta');
    $document->setMeta('second', 'second_meta');

    $this->assertEquals(
      'first_meta',
      $document->getMeta('first')
    );
  }

  public function testSetOG(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setOG('test', 'test_og');

    $this->assertEquals(['og:test' => 'test_og'], $document->getOG());
  }

  public function testGetAllOG(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setOG('first', 'first_og');
    $document->setOG('second', 'second_og');

    $this->assertEquals(
      ['og:first' => 'first_og', 'og:second' => 'second_og'],
      $document->getOG()
    );
  }

  public function testGetSingleOG(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setOG('first', 'first_og');
    $document->setOG('second', 'second_og');

    $this->assertEquals(
      'first_og',
      $document->getOG('first')
    );
  }

  public function testAddLink(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->addLink('test_href', 'test');

    $this->assertEquals(
      ['test_href' => ['rel' => 'test', 'attributes' => []]],
      $document->getLinks()
    );
  }

  public function testSetFavicon(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setFavicon('/test.ico');

    $this->assertEquals('/test.ico', $document->getFavicon());
  }

  public function testAddStylesheet(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->addStylesheet('test.css');

    $this->assertEquals(
      ['test.css' => ['rel' => 'stylesheet', 'attributes' => []]],
      $document->getLinks()
    );
  }

  public function testAddInlineStyle(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->addInlineStyle('.test { color: red; }');

    $this->assertContains(
      ['content' => '.test { color: red; }'],
      $document->getInlineStyles()
    );
  }

  public function testAddScriptToHead(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->addScript('test.js');

    $this->assertContains(
      ['inline' => false, 'src' => 'test.js', 'attributes' => []],
      $document->getHeadScripts()
    );
  }

  public function testAddScriptToBody(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->addScript('test.js', [], true);

    $this->assertContains(
      ['inline' => false, 'src' => 'test.js', 'attributes' => []],
      $document->getBodyScripts()
    );
  }

  public function testAddInlineScriptToHead(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->addInlineScript('alert(1)');

    $this->assertContains(
      ['inline' => true, 'content' => 'alert(1)', 'attributes' => []],
      $document->getHeadScripts()
    );
  }

  public function testAddInlineScriptToBody(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->addInlineScript('alert(1)', [], true);

    $this->assertContains(
      ['inline' => true, 'content' => 'alert(1)', 'attributes' => []],
      $document->getBodyScripts()
    );
  }

  public function testImplodeAttributes(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $attributes = ['test' => 'test_value', 'custom' => 'custom_value'];

    $this->assertEquals(
      'test="test_value" custom="custom_value"',
      $document->implodeAttributes($attributes)
    );
  }

  public function testRenderHead(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->setTitle('Test');
    $document->setDescription('Document test');
    $document->setMeta('test', 'test_meta');
    $document->setOG('test', 'test_og');
    $document->setFavicon('/test.ico');
    $document->addLink('test_href', 'test');
    $document->addScript('test.js');
    $document->addInlineScript('alert(1)');
    $document->addInlineStyle('.test { color: red; }');

    $expected = "<title>Test</title>\n";
    $expected .= "<meta name=\"description\" content=\"Document test\">\n";
    $expected .= "<meta name=\"test\" content=\"test_meta\">\n";
    $expected .= "<meta property=\"og:test\" content=\"test_og\">\n";
    $expected .= "<link href=\"/test.ico\" rel=\"icon\" type=\"image/x-icon\">\n";
    $expected .= "<link href=\"test_href\" rel=\"test\" >\n";
    $expected .= "<script src=\"test.js\" ></script>\n";
    $expected .= "<script >alert(1)</script>\n";
    $expected .= "<style>.test { color: red; }</style>\n";

    $this->assertEquals($expected, $document->renderHead());
  }

  public function testRenderBodyScripts(): void {
    App::create($this->testRootPath);

    $document = App::getDocument();
    $document->addScript('test.js', [], true);
    $document->addInlineScript('alert(1)', [], true);

    $expected = "<script src=\"test.js\" ></script>\n";
    $expected .= "<script >alert(1)</script>\n";

    $this->assertEquals($expected, $document->renderBodyScripts());
  }
}
