<?php
/**
 * Filippo Finke
 * Database
 * 
 * Classe che permette di collegarsi al database.
 */
class Database {

    /**
     * Variabile di connessione.
     */
    private static $connection = null;

    /**
     * Metodo getter per la connessione.
     */
    public static function get() {
        return self::$connection;
    }

    /**
     * Metodo utilizzato per collegarsi al database.
     */
    public static function connect($settings) {
        try {
            self::$connection = new PDO('mysql:host='.$settings["host"].';dbname='.$settings["dbname"], $settings["user"], $settings["password"], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ));
        }
        catch(PDOException $e) {
            exit("Impossibile collegarsi al database. ".$e->getMessage());
        }
    }

}