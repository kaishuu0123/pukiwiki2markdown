<?php

namespace Tests\Functional;

class HomepageTest extends BaseTestCase
{
    public function testGetHomepage(): void
    {
        $response = $this->runApp('GET', '/');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('pukiwiki2markdown', (string) $response->getBody());
    }

    public function testPostHomepageNotAllowed(): void
    {
        $response = $this->runApp('POST', '/', ['test']);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertStringContainsString('Method not allowed', (string) $response->getBody());
    }
}
