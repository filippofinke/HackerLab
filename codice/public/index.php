<?php
if (PHP_SAPI == 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

$settings = array(
    'settings' => [
        'displayErrorDetails' => true
    ]
);

$app = new \Slim\App($settings);

/**
 * Aggiungo le dipendenze al sito.
 */
$dependencies = require __DIR__ . '/../src/dependencies.php';
$dependencies($app);

/**
 * Registro i percorsi.
 */
$routes = require __DIR__ . '/../src/routes.php';
$routes($app);


/**
 * Avvio il sito web.
 */
$app->run();
