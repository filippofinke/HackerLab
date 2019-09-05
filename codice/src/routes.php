<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {

    $app->get('/', function (Request $request, Response $response, array $args) use ($app) {
        $this->view->render($response, "index.phtml", $args);
    });

};
