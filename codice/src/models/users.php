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

    public static function login($email, $password) {
        $user = self::getByEmail($email);
        if($user) {
            if(password_verify($password, $user["password"])) {
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

}

?>