<?php
declare(strict_types=1);

namespace Tests\Unit\Quark;

use Tests\QuarkTestCase;

class QuarkEngineTest extends QuarkTestCase {
  public function testEngineInitializes(): void {
    $this->assertDirectoryExists($this->testCachePath);

    $reflection = new \ReflectionClass($this->engine);
    $property = $reflection->getProperty('filters');
    $property->setAccessible(true);
    $filters = $property->getValue($this->engine);

    $this->assertEquals(
      [
        'upper',
        'lower',
        'capitalize',
        'length',
        'reverse',
        'sort',
        'join',
        'default',
        'date',
        'truncate',
        'raw',
        'json',
        'dump',
      ],
      array_keys($filters)
    );
  }

  public function testRenderTemplate(): void {
    $this->createTestTemplate();

    $this->assertEquals(
      '<div>Test Value</div>',
      $this->engine->render('test', ['test' => 'Test Value'])
    );
  }

  public function testRenderTemplateWithLayout(): void {
    $this->createTestTemplate('layout', '<div>{% outlet %}</div>');
    $this->createTestTemplate('test', '{% layout layout %}<p>{{ test }}</p>');

    $this->assertEquals(
      '<div><p>Test Value</p></div>',
      $this->engine->render('test', ['test' => 'Test Value'])
    );
  }

  public function testRenderTemplateWithRootLayout(): void {
    $this->createTestTemplate('root', '<div>{% outlet %}</div>');
    $this->createTestTemplate('test', '<p>{{ test }}</p>');
    $this->engine->setRootLayout('root');

    $this->assertEquals(
      '<div><p>Test Value</p></div>',
      $this->engine->render('test', ['test' => 'Test Value'])
    );
  }

  public function testRenderTemplateWithSkipRootLayout(): void {
    $this->createTestTemplate('root', '<div>{% outlet %}</div>');
    $this->createTestTemplate('test', '<p>{{ test }}</p>');
    $this->engine->setRootLayout('root');
    $this->engine->skipRootLayout();

    $this->assertEquals(
      '<p>Test Value</p>',
      $this->engine->render('test', ['test' => 'Test Value'])
    );
  }

  public function testRenderTemplateWithSkipRootDirective(): void {
    $this->createTestTemplate('root', '<div>{% outlet %}</div>');
    $this->createTestTemplate('test', '{% skip_root %}<p>{{ test }}</p>');
    $this->engine->setRootLayout('root');

    $this->assertEquals(
      '<p>Test Value</p>',
      $this->engine->render('test', ['test' => 'Test Value'])
    );
  }

  public function testRenderTemplateWithCustomOutlet(): void {
    $this->createTestTemplate('layout', '<div>{% outlet custom %}</div>');
    $this->createTestTemplate('test', '{% layout layout %}{% slot custom %}<p>{{ test }}</p>{% endslot %}');

    $this->assertEquals(
      '<div><p>Test Value</p></div>',
      $this->engine->render('test', ['test' => 'Test Value'])
    );
  }

  public function testRenderTemplateWithIf(): void {
    $this->createTestTemplate('test', '<div>{% if $test %}True{% endif %}</div>');

    $this->assertEquals(
      '<div></div>',
      $this->engine->render('test', ['test' => false])
    );
    $this->assertEquals(
      '<div>True</div>',
      $this->engine->render('test', ['test' => true])
    );
  }

  public function testRenderTemplateWithIfWithElses(): void {
    $this->createTestTemplate(
      'test',
      '<div>{% if $test == 1 %}One{% elseif $test == 2 %}Two{% else %}None{% endif %}</div>'
    );

    $this->assertEquals(
      '<div>One</div>',
      $this->engine->render('test', ['test' => 1])
    );
    $this->assertEquals(
      '<div>Two</div>',
      $this->engine->render('test', ['test' => 2])
    );
    $this->assertEquals(
      '<div>None</div>',
      $this->engine->render('test', ['test' => 3])
    );
  }

  public function testRenderTemplateWithForeach(): void {
    $this->createTestTemplate(
      'test',
      '<div>{% foreach $test as $item %}<p>{{ $item[\'name\'] }}</p>{% endforeach %}</div>'
    );

    $this->assertEquals(
      '<div><p>first</p><p>second</p><p>third</p></div>',
      $this->engine->render('test', ['test' => [
        ['name' => 'first'],
        ['name' => 'second'],
        ['name' => 'third'],
      ]])
    );
  }

  public function testRenderTemplateWithSet(): void {
    $this->createTestTemplate(
      'test',
      '<div>{% if $test %}{% set $text = \'new_value\' %}{% endif %}{{ text }}</div>'
    );

    $this->assertEquals(
      '<div>default</div>',
      $this->engine->render('test', ['test' => false, 'text' => 'default'])
    );
    $this->assertEquals(
      '<div>new_value</div>',
      $this->engine->render('test', ['test' => true, 'text' => 'default'])
    );
  }

  public function testEscapeHtml(): void {
    $this->assertEquals(
      '&lt;div&gt;test&lt;/div&gt;',
      $this->engine->escape('<div>test</div>')
    );
  }

  public function testEscapeAttribute(): void {
    $this->assertEquals(
      '&quot;test!&quot;',
      $this->engine->escape('"test!"', 'attr')
    );
  }

  public function testEscapeJs(): void {
    $this->assertEquals(
      '"this string\u0027s a test \u0026 \u003Cdiv\u003Etest\u003C\/div\u003E"',
      $this->engine->escape('this string\'s a test & <div>test</div>', 'js')
    );
  }

  public function testEscapeCss(): void {
    $this->assertEquals(
      'csstest',
      $this->engine->escape('{css test}', 'css')
    );
  }

  public function testEscapeUrl(): void {
    $this->assertEquals(
      'this%20is%20a%20test',
      $this->engine->escape('this is a test', 'url')
    );
  }

  public function testEscapeRaw(): void {
    $this->assertEquals(
      '<div>test</div>',
      $this->engine->escape('<div>test</div>', 'raw')
    );
  }

  public function testFilterUpper(): void {
    $this->assertEquals(
      'TEST',
      $this->engine->applyFilter('upper', 'test')
    );
  }

  public function testFilterLower(): void {
    $this->assertEquals(
      'test',
      $this->engine->applyFilter('lower', 'TEST')
    );
  }

  public function testFilterCapitalize(): void {
    $this->assertEquals(
      'Test',
      $this->engine->applyFilter('capitalize', 'test')
    );
  }

  public function testFilterLengthWithString(): void {
    $this->assertEquals(
      4,
      $this->engine->applyFilter('length', 'test')
    );
  }

  public function testFilterLengthWithArray(): void {
    $this->assertEquals(
      2,
      $this->engine->applyFilter('length', ['first', 'second'])
    );
  }

  public function testFilterReverseWithString(): void {
    $this->assertEquals(
      'tset',
      $this->engine->applyFilter('reverse', 'test')
    );
  }

  public function testFilterReverseWithArray(): void {
    $this->assertEquals(
      ['second', 'first'],
      $this->engine->applyFilter('reverse', ['first', 'second'])
    );
  }

  public function testFilterSort(): void {
    $this->assertEquals(
      [1, 2, 3, 4],
      $this->engine->applyFilter('sort', [3, 1, 4, 2])
    );
  }

  public function testFilterJoin(): void {
    $this->assertEquals(
      '1, 2, 3, 4',
      $this->engine->applyFilter('join', [1, 2, 3, 4])
    );
  }

  public function testFilterJoinWithSeparator(): void {
    $this->assertEquals(
      '1.2.3.4',
      $this->engine->applyFilter('join', [1, 2, 3, 4], '.')
    );
  }

  public function testFilterDefault(): void {
    $this->assertEquals(
      'set_value',
      $this->engine->applyFilter('default', 'set_value', 'default_value')
    );
    $this->assertEquals(
      'default_value',
      $this->engine->applyFilter('default', null, 'default_value')
    );
  }

  public function testFilterDate(): void {
    $this->assertEquals(
      '2000-12-20',
      $this->engine->applyFilter('date', '12/20/2000')
    );
  }

  public function testFilterDateWithFormat(): void {
    $this->assertEquals(
      'Dec 20, 2000',
      $this->engine->applyFilter('date', '12/20/2000', 'M d, Y')
    );
  }

  public function testFilterTruncate(): void {
    $result = $this->engine->applyFilter('truncate', join('', array_fill(0, 150, '0')));
    $this->assertEquals(
      100 + strlen('...'),
      strlen($result)
    );
  }

  public function testFilterTruncateWithLength(): void {
    $result = $this->engine->applyFilter('truncate', join('', array_fill(0, 150, '0')), 20);
    $this->assertEquals(
      20 + strlen('...'),
      strlen($result)
    );
  }

  public function testFilterRaw(): void {
    $this->assertEquals(
      '<div>test</div>',
      $this->engine->applyFilter('raw', '<div>test</div>')
    );
  }

  public function testFilterJson(): void {
    $this->assertEquals(
      '{"test":"test"}',
      $this->engine->applyFilter('json', ['test' => 'test'])
    );
  }

  public function testFilterDump(): void {
    $this->assertEquals(
      "<pre>Array\n(\n    [test] =&gt; test\n)\n</pre>",
      $this->engine->applyFilter('dump', ['test' => 'test'])
    );
  }

  public function testAddFilter(): void {
    $this->engine->addFilter('test', function ($value) {
      return 'test_filter_ran';
    });

    $reflection = new \ReflectionClass($this->engine);
    $property = $reflection->getProperty('filters');
    $property->setAccessible(true);
    $filters = $property->getValue($this->engine);

    $this->assertArrayHasKey('test', $filters);
    $this->assertEquals(
      'test_filter_ran',
      $this->engine->applyFilter('test', 'test_value')
    );
  }

  public function testAddGlobal(): void {
    $this->createTestTemplate('test', '<p>{{ test }}</p>');
    $this->engine->addGlobal('test', 'test_global_value');

    $this->assertEquals(
      '<p>test_global_value</p>',
      $this->engine->render('test')
    );
  }

  public function testCompiledTemplateIsCached(): void {
    $this->createTestTemplate();
    $this->engine->render('test', ['test' => 'Test Value']);
    $key = md5($this->testTemplatePath . '/test.quark.html');

    $this->assertFileExists($this->testCachePath . '/' . $key . '.php');
    $this->assertEquals(
      "<?php\necho '<div>';\necho \$__quark->escape(\$test);\necho '</div>';\n",
      file_get_contents($this->testCachePath . '/' . $key . '.php')
    );
  }
}
