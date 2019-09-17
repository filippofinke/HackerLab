<?php 
class Comments {

    public static function getByArticleId($article_id) {
        $query = Database::get()->prepare("SELECT *, (SELECT full_name FROM users WHERE id = user_id) as 'full_name' FROM comments WHERE article_id = :article_id ORDER BY created_at DESC");
        $query->bindParam(":article_id", $article_id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function insert($article_id, $user_id, $comment) {
        $comment = htmlspecialchars($comment);
        if(strlen($comment) > 255) {
            $_SESSION["error"] = "Il commento non può essere più lungo di 255 caratteri!";
            return false;
        }
        $query = Database::get()->prepare("INSERT INTO comments(article_id, user_id, comment) VALUES (:article_id, :user_id, :comment)");
        $query->bindParam(":article_id", $article_id);
        $query->bindParam(":user_id", $user_id);
        $query->bindParam(":comment", $comment);
        $query->execute();
        if($query) {
            return true;
        } else {
            $_SESSION["error"] = "Impossibile inserire il commento!";
            return false;
        }
    }

}
?>