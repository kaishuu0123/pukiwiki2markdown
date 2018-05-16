<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

error_reporting(E_ERROR | E_WARNING | E_PARSE);

$mw = function($req, $res, $args){
    $params = [];
    $params['get'] = $req->getQueryParams();
    $params['post'] = $req->getParsedBody();

    // 連想配列をオブジェクトに変換して，'params'というkeyに登録
    $req = $req->withAttribute('params', json_decode(json_encode($params)));

    $response = $next($req, $res);
    return $response;
};