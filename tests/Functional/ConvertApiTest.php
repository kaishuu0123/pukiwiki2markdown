<?php

namespace Tests\Functional;

class ConvertApiTest extends BaseTestCase
{
    public function testConvertReturnsJsonWithMarkdown(): void
    {
        $response = $this->runApp('POST', '/api/v1/convert', ['body' => "*見出し\n\n- リスト\n"]);

        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($json);
        $this->assertStringContainsString('# 見出し', $json['body']);
        $this->assertStringContainsString('* リスト', $json['body']);
    }

    public function testConvertGetIsNotAllowed(): void
    {
        $response = $this->runApp('GET', '/api/v1/convert');

        $this->assertSame(405, $response->getStatusCode());
    }
}
