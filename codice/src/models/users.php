<?php
/**
 * Filippo Finke
 * Users
 * 
 * Classe che permette di interfaccarsi con la tabella USERS.
 */

class Users {

    /**
     * Metodo che permette di ricavare tutti gli utenti.
     * 
     * @return Array Gli utenti.
     */
    public static function get() {
        $query = Database::get()->prepare("SELECT * FROM users ORDER BY permission ASC, full_name ASC");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Metodo che permette di ricavare un utente.
     * 
     * @param Integer $user_id L'id dell'utente.
     * @return Array L'utente.
     */
    public static function getById($user_id) {
        $query = Database::get()->prepare("SELECT * FROM users WHERE id = :user_id");
        $query->bindParam(":user_id", $user_id);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Metodo che permette di ricavare un utente dalla email.
     * 
     * @param String $email L'email.
     * @return Array L'utente.
     */
    public static function getByEmail($email) {
        $query = Database::get()->prepare("SELECT * FROM users WHERE email = :email");
        $query->bindParam(":email", $email);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Metodo che permette di eliminare un utente.
     * 
     * @param Integer $user_id L'id dell'utente.
     * @return Boolean Se l'utente è stato eliminato oppure no.
     */
    public static function delete($user_id) {
        if($user_id == $_SESSION["user"]["id"]) {
            $_SESSION["error"] = "Non puoi eliminare l'utente corrente!";
            return false;
        }
        $query = Database::get()->prepare("DELETE FROM users WHERE id = :user_id");
        $query->bindParam(":user_id", $user_id);
        $query->execute();
        if(!$query) {
            $_SESSION["error"] = "Impossibile eliminare l'utente!";
            return false;
        }
        $_SESSION["success"] = "Utente eliminato!";
        return true;
    }

    /**
     * Metodo che permette di resettare la password di un utente.
     * 
     * @param String $reset_token Il token di recupero password.
     * @param String $password La nuova password.
     * @param String $repeat_password La nuova password ripetuta.
     * @return Boolean Se la password è stata reimpostata oppure no. 
     */
    public static function resetPassword($reset_token, $password, $repeat_password) {
        if(strlen($password) < 4) {
            $_SESSION["reset_password"] = "La password deve avere almeno 4 caratteri!";
            return false;
        }
        if($password != $repeat_password) {
            $_SESSION["reset_password"] = "Le due password non corrispondono!";
            return false;
        }
        $query = Database::get()->prepare("SELECT * FROM users WHERE reset_token = :reset_token");
        $query->bindParam(":reset_token", $reset_token);
        $query->execute();
        $user = $query->fetch(PDO::FETCH_ASSOC);
        if($user) {
            $query = Database::get()->prepare("UPDATE users SET password = :password, reset_token = null WHERE reset_token = :reset_token");
            $query->bindParam(":password", password_hash($password, PASSWORD_DEFAULT));
            $query->bindParam(":reset_token", $reset_token, PDO::PARAM_STR);
            $query->execute();
            if(!$query) {
                $_SESSION["reset_password"] = "Impossibile impostare la password!";
                return false;
            }
            $_SESSION["success"] = "Password impostata con successo!";
            return true;
        } else {
            $_SESSION["reset_password"] = "Codice di recupero invalido!";
            return false;
        }
    }

    /**
     * Metodo che permette di generare un token di recupero per un utente.
     * 
     * @param String $email L'email.
     * @return Boolean Se il token è stato creato oppure no.
     */
    public static function generateResetToken($email) {
        $user = self::getByEmail($email);
        if($user) {

            /**
             * VULNERABILE!
             */
            $reset_token = base64_encode(time());

            $query = Database::get()->prepare("UPDATE users SET reset_token = :reset_token WHERE email = :email");
            $query->bindParam(":reset_token", $reset_token);
            $query->bindParam(":email", $email);
            $query->execute();
            if(!$query) {
                $_SESSION["error"] = "Impossibile resettare la password!";
                return false;
            }
            $message = "Recupera la tua password premendo il seguente link:<br>http://hackerlab.ch/?reset_token=".urlencode($reset_token);
            if(Mailer::send($email, $user["full_name"], "Recupera password", $message)) {
                $_SESSION["success"] = "Email di recupero inviata!";
                return true;
            } else {
                $_SESSION["error"] = "Impossibile resettare la password!";
                return false;
            }
        }
        $_SESSION["error"] = "Account inesistente!";
        return false;
    
    }

    /**
     * Metodo utilizzato per autenticare un utente.
     * 
     * @param String $email L'email dell'utente.
     * @param String $password La password dell'utente.
     * @return Boolean Se le credenziali sono corrette oppure no.
     */
    public static function login($email, $password) {
        $user = self::getByEmail($email);
        if($user) {
            if (!$user["enabled"]) {
                $_SESSION["error"] = "Account disabilitato!";
                return false;
            } else if(password_verify($password, $user["password"])) {
                /**
                 * VULNERABILE!
                 */
                setcookie('permission', base64_encode($user["permission"]));
                $_SESSION["user"] = array(
                    'id' => $user["id"],
                    'email' => $user["email"],
                    'full_name' => $user["full_name"],
                    'permission' => $user["permission"]
                );
                return true;
            }
        }
        $_SESSION["error"] = "Email o password errati!";
        return false;
    } 

    /**
     * Metodo che permette di registrare un utente.
     * 
     * @param String $full_name Il nome completo.
     * @param String $email L'email.
     * @param String $password La password.
     * @param String $repeat_password La password ripetuta.
     * @return Boolean Se l'utente è stato creato oppure no.
     */
    public static function register($full_name, $email, $password, $repeat_password) {
        
        $email = strtolower($email);

        if(!preg_match('/^[a-zA-Z]*\s{0,1}[a-zA-Z]*$/', $full_name)) {
            $_SESSION["error"] = "Nome e cognome possono contenere solamente caratteri dell'alfabeto.";
            return false;
        }

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION["error"] = "L'email inserita non è valida!";
            return false;
        }

        if(strlen($password) < 4) {
            $_SESSION["error"] = "La password deve avere almeno 4 caratteri!";
            return false;
        }

        if($password != $repeat_password) {
            $_SESSION["error"] = "Le due password non corrispondono!";
            return false;
        }

        if (!self::getByEmail($email)) {
            $query = Database::get()->prepare("INSERT INTO users VALUES(null, :email, :password, :permission, :full_name, null, 1)");
            $query->bindParam(":email", $email);
            $query->bindParam(":password", password_hash($password,PASSWORD_DEFAULT));
            $query->bindValue(":permission", "user");
            $query->bindParam(":full_name", $full_name);
            $query->execute();
            if(!$query) {
                $_SESSION["error"] = "Impossibile creare un account!";
                return false;
            }
            self::login($email, $password);
            return true;
        } else {
            $_SESSION["error"] = "Un account con questa email esiste già!";
            return false;
        }

    }

}

?>