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
    ],
    'database' => [
        'host' => '127.0.0.1',
        'user' => 'root',
        'password' => 'filospinato22',
        'dbname' => 'hackerlab'
    ]
);

class Database {

    private static $connection;

    public static function get() {
        return self::$connection;
    }

    public static function connect($settings) {
        self::$connection = new PDO('mysql:host='.$settings["host"].';dbname='.$settings["dbname"], $settings["user"], $settings["password"], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ));
    }

}
Database::connect($settings["database"]);
require __DIR__ . '/../src/mailer.php';
require __DIR__ . '/../src/models/users.php';
require __DIR__ . '/../src/models/articles.php';
require __DIR__ . '/../src/models/comments.php';

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
