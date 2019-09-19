<?php

class Users {


    public static function getById($user_id) {
        $query = Database::get()->prepare("SELECT * FROM users WHERE id = :user_id");
        $query->bindParam(":user_id", $user_id);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public static function getByEmail($email) {
        $query = Database::get()->prepare("SELECT * FROM users WHERE email = :email");
        $query->bindParam(":email", $email);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public static function generateResetToken($email) {
        $user = self::getByEmail($email);
        if($user) {
            $reset_token = base64_encode(time());
            $query = Database::get()->prepare("UPDATE users SET reset_token = :reset_token WHERE email = :email");
            $query->bindParam(":reset_token", $reset_token);
            $query->bindParam(":email", $email);
            $query->execute();
            if(!$query) {
                $_SESSION["error"] = "Impossibile resettare la password!";
                return false;
            }
            $message = "Recupera la tua password premendo il seguente link:\nhttp://127.0.0.1/?reset_token=".$reset_token;
            if(Mailer::send($email, $user["full_name"], "Conferma account", $message)) {
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

    public static function login($email, $password) {
        $user = self::getByEmail($email);
        if($user) {
            if (!$user["enabled"]) {
                $_SESSION["error"] = "Account disabilitato!";
                return false;
            } else if(password_verify($password, $user["password"])) {
                setcookie('permission', base64_encode($user["permission"]));
                $_SESSION["user"] = array(
                    'id' => $user["id"],
                    'email' => $user["email"],
                    'full_name' => $user["full_name"]
                );
                return true;
            }
        }
        $_SESSION["error"] = "Email o password errati!";
        return false;
    } 

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