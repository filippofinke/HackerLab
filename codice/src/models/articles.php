<?php

class Articles {

    private static $limit = 3;

    public static function search($search, $page) {
        $limit = self::$limit;
        $offset = $limit * $page;
        $search = "%".str_replace("%","",$search)."%";
        $query = Database::get()->prepare("SELECT *, (SELECT full_name FROM users WHERE id = user_id) as 'full_name' FROM articles WHERE title LIKE :search ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $query->bindParam(":search", $search);
        $query->bindParam(":limit", $limit, PDO::PARAM_INT);
        $query->bindParam(":offset", $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function get($page) {
        $limit = self::$limit;
        $offset = $limit * $page;
        $query = Database::get()->prepare("SELECT *, (SELECT full_name FROM users WHERE id = user_id) as 'full_name' FROM articles ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $query->bindParam(":limit", $limit, PDO::PARAM_INT);
        $query->bindParam(":offset", $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($post_id) {
        $query = Database::get()->prepare("SELECT *, (SELECT full_name FROM users WHERE id = user_id) as 'full_name' FROM articles WHERE id = :post_id");
        $query->bindParam(":post_id", $post_id);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public static function getByUserId($user_id) {
        $query = Database::get()->prepare("SELECT * FROM articles WHERE user_id = :user_id ORDER BY created_at DESC");
        $query->bindParam(":user_id", $user_id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function insert($user_id, $title, $image, $content) {
        
        $title = htmlspecialchars($title);
        $content = strip_tags($content, '<h1><ul><li><a><br>');

        if(strlen($title) > 255) {
            $_SESSION["article_error"] = "Il titolo non può essere più lungo di 255 caratteri!";
            return false;
        }

        if(strlen($content) > 1000) {
            $_SESSION["article_error"] = "Il contenuto dell'articolo non può superare i 1000 caratteri!";
            return false;
        }
        $file_name = null;
        if ($image != null) {
            $tmp_path = $image["tmp_name"];
            $file_name = hash("sha256", file_get_contents($tmp_path));
            if(!move_uploaded_file($tmp_path, __DIR__.'/../../storage/'.$file_name)) {
                $_SESSION["article_error"] = "Impossibile caricare l'immagine di copertina!";
                return false;
            }
        }

        $query = Database::get()->prepare("INSERT INTO articles(user_id, title, image, content) VALUES (:user_id, :title, :image, :content)");
        $query->bindParam(":user_id", $user_id);
        $query->bindParam(":title", $title);
        $query->bindParam(":image", $file_name);
        $query->bindParam(":content", $content);
        $query->execute();
        if(!$query) {
            $_SESSION["article_error"] = "Impossibile inserire l'articolo!";
        }
        return true;
    }

}

?>