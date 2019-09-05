<?php

use Slim\App;

return function (App $app) {
    $container = $app->getContainer();

    $container['view'] = function ($c) {
        return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates/');
    };

    $container['notFoundHandler'] = function ($c) {
        return function ($request, $response) use ($c) {
            return $response->withRedirect('/', 302);
        };
    };

};
