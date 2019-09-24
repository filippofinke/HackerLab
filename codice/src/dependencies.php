<?php
/**
 * Filippo Finke
 * Dipendenze
 */
use Slim\App;

return function (App $app) {
    /**
     * Contenitore delle dipendenze del framework Slim.
     */
    $container = $app->getContainer();

    /**
     * Includo la dipendeza per renderizzare i template.
     */
    $container['view'] = function ($c) {
        return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates/');
    };

    /**
     * Imposto un redirect nel caso ci sia un errore 404.
     */
    $container['notFoundHandler'] = function ($c) {
        return function ($request, $response) use ($c) {
            return $response->withRedirect('/', 302);
        };
    };

};
