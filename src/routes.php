<?php

use Slim\Http\Request;
use Slim\Http\Response;

require 'pukiwiki_func/convert_html.php';

// Routes

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->post('/api/v1/convert', function (Request $request, Response $response, array $args) {
    $parsedBody = $request->getParsedBody();

    $html = convert_html($parsedBody['body']);
    $escaped = $html;
    $body = $response->getBody();
    $hash = array("body" => $escaped);
    $body->write(json_encode($hash));

    return $response;
});