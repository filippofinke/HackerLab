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
        $query = Database::get()->prepare("SELECT * FROM articles WHERE user_id = :user_id");
        $query->bindParam(":user_id", $user_id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

}

?>