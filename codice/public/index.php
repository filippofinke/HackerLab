<?php

// Aiuta il server web di php ad identificare se la richiesta è di un file.
if (PHP_SAPI == 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

// Includo le librerie attraverso l'autoload di composer.
require __DIR__ . '/../vendor/autoload.php';

// Avvio la sessione.
session_start();

// Impostazioni del sito web.
$settings = array(
    'settings' => [
        // Abilita i messaggi di errore a schermo.
        'displayErrorDetails' => true
    ],
    'database' => [
        'host' => '127.0.0.1',
        'user' => 'root',
        'password' => 'filospinato22',
        'dbname' => 'hackerlab'
    ]
);

// Includo la classe per gestire il database.
require __DIR__ . '/../src/database.php';
// Mi collego al database.
Database::connect($settings["database"]);

// Includo le classi utilizzate dall'applicativo.
require __DIR__ . '/../src/mailer.php';
require __DIR__ . '/../src/models/users.php';
require __DIR__ . '/../src/models/articles.php';
require __DIR__ . '/../src/models/comments.php';

// Creo un oggetto di tipo Slim.
$app = new \Slim\App($settings);

// Aggiungo le dipendeze.
$dependencies = require __DIR__ . '/../src/dependencies.php';
$dependencies($app);

// Registro i percorsi.
$routes = require __DIR__ . '/../src/routes.php';
$routes($app);

// Avvio l'applicativo.
$app->run();