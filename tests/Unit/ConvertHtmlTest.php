<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/pukiwiki_func/convert_html.php';

/**
 * PukiWiki 記法 → Markdown 変換 (convert_html) のテスト。
 * PHP 8 化で壊れやすかった旧式コンストラクタ等のリグレッション検知も兼ねる。
 */
class ConvertHtmlTest extends TestCase
{
    #[DataProvider('provideConversions')]
    public function testConvert(string $wiki, string $expectedFragment): void
    {
        $this->assertStringContainsString($expectedFragment, convert_html($wiki));
    }

    public static function provideConversions(): array
    {
        return [
            'heading'       => ["*見出し\n", '# 見出し'],
            'heading level2' => ["**小見出し\n", '## 小見出し'],
            'list'          => ["- a\n- b\n", '* a'],
            'nested list'   => ["- a\n-- b\n", '    * b'],
            'ordered list'  => ["+ one\n", '1. one'],
            'bold'          => ["''強調''\n", '<strong>強調</strong>'],
            'link'          => ["[[name>http://example.com]]\n", '<a href="http://example.com" rel="nofollow">name</a>'],
            'auto link'     => ["http://example.org\n", '<a href="http://example.org"'],
            'table'         => ["|a|b|\n|c|d|\n", '<td>a</td>'],
            'quote'         => ["> 引用\n", '<blockquote>引用'],
            'unknown plugin' => ["#unknownplugin\n", 'プラグインには対応していません'],
        ];
    }

    public function testConvertAcceptsArrayOfLines(): void
    {
        $this->assertStringContainsString('# 見出し', convert_html(["*見出し"]));
    }

    public function testCommentLinesAreIgnored(): void
    {
        $this->assertStringNotContainsString('コメント', convert_html("//コメント\n"));
    }

    public function testConvertEmitsNoWarnings(): void
    {
        $errors = [];
        set_error_handler(function (int $no, string $str) use (&$errors) {
            $errors[] = $str;
            return true;
        });
        try {
            convert_html("*見出し[#abc]\n\n- a\n\n,a,b\n,c,d\n\nfoo((note))\n");
        } finally {
            restore_error_handler();
        }
        $this->assertSame([], $errors);
    }
}
