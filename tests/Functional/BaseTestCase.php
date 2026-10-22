<?php

namespace Tests\Functional;

use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Slim アプリ全体を組み立ててリクエストを処理するための基底クラス
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * Use middleware when running application?
     */
    protected bool $withMiddleware = true;

    /**
     * Process the application given a request method and URI
     *
     * @param string $requestMethod the request method (e.g. GET, POST, etc.)
     * @param string $requestUri the request URI
     * @param array|object|null $requestData the request data
     */
    public function runApp(string $requestMethod, string $requestUri, array|object|null $requestData = null): Response
    {
        $environment = Environment::mock([
            'REQUEST_METHOD' => $requestMethod,
            'REQUEST_URI' => $requestUri,
        ]);

        $request = Request::createFromEnvironment($environment);

        if (isset($requestData)) {
            $request = $request->withParsedBody($requestData);
        }

        $response = new Response();

        $settings = require __DIR__ . '/../../src/settings.php';
        $app = new App($settings);

        require __DIR__ . '/../../src/dependencies.php';

        if ($this->withMiddleware) {
            require __DIR__ . '/../../src/middleware.php';
        }

        require __DIR__ . '/../../src/routes.php';

        return $app->process($request, $response);
    }
}
