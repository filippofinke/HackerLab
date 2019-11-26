<?php
/**
 * Filippo Finke
 * Articles
 *
 * Classe che permette di interfaccarsi con la tabella ARTICLES.
 */

class Articles
{

    /**
     * Numero di articoli per pagina.
     */
    private static $limit = 3;

    /**
     * Metodo che permette di eseguire una ricerca tra gli articoli.
     *
     * @param String $search La ricerca da effettuare.
     * @param Integer $page Il numero di pagina.
     * @return Array I risultati della ricerca
     */
    public static function search($search, $page)
    {
        $limit = self::$limit;
        $offset = $limit * $page;
        $search = "'%".$search."%'";
        /**
         * ATTENZIONE: La query di ricerca non utilizza dei prepared statements.
         *             Questa funzione permette quindi di eseguire del codice SQL
         *             malevolo. ES: SQL Injection "a%'; DELETE FROM articles; --"
         */
        $query = Database::get()->query("SELECT *, (SELECT full_name FROM users WHERE id = user_id) as 'full_name' FROM articles WHERE title LIKE $search ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Metodo che permette di ricavare gli articoli di una pagina.
     *
     * @param Integer $page Il numero di pagina.
     * @return Array I risultato della pagina.
     */
    public static function get($page)
    {
        $limit = self::$limit;
        $offset = $limit * $page;
        $query = Database::get()->prepare("SELECT *, (SELECT full_name FROM users WHERE id = user_id) as 'full_name' FROM articles ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $query->bindParam(":limit", $limit, PDO::PARAM_INT);
        $query->bindParam(":offset", $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Metodo che permette di ricavare la pagina massima di una ricerca.
     *
     * @param String $search La ricerca.
     * @return Integer La pagina massima.
     */
    public static function getSearchMaxPage($search)
    {
        $search = '%'.$search.'%';
        $query = Database::get()->prepare("SELECT COUNT(*) FROM articles WHERE title LIKE :title");
        $query->bindParam(":title", $search, PDO::PARAM_STR);
        $query->execute();
        $maxPage =  ceil($query->fetch(PDO::FETCH_NUM)[0] / self::$limit) - 1;
        if ($maxPage < 0) {
            $maxPage = 0;
        }
        return $maxPage;
    }

    /**
     * Metodo che permette di ricavare la pagina massima.
     *
     * @return Integer La pagina massima.
     */
    public static function getMaxPage()
    {
        $query = Database::get()->query("SELECT COUNT(*) FROM articles");
        $maxPage =  ceil($query->fetch(PDO::FETCH_NUM)[0] / self::$limit) - 1;
        if ($maxPage < 0) {
            $maxPage = 0;
        }
        return $maxPage;
    }

    /**
     * Metodo che permette di ricavare un articolo attraverso il suo id.
     *
     * @param Integer $article_id L'id dell'articolo.
     * @return Array L'articolo.
     */
    public static function getById($article_id)
    {
        $query = Database::get()->prepare("SELECT *, (SELECT full_name FROM users WHERE id = user_id) as 'full_name' FROM articles WHERE id = :article_id");
        $query->bindParam(":article_id", $article_id);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Metodo che permette di ricavare tutti gli articoli di un utente.
     *
     * @param Integer $user_id L'id dell'utente.
     * @return Array Gli articoli creati dall'utente.
     */
    public static function getByUserId($user_id)
    {
        $query = Database::get()->prepare("SELECT * FROM articles WHERE user_id = :user_id ORDER BY created_at DESC");
        $query->bindParam(":user_id", $user_id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Metodo che permette di inserire un articolo.
     *
     * @param Integer $user_id L'id dell'utente.
     * @param String $title Il titolo dell'articolo.
     * @param Array $image Array contenente le informazioni dell'immagine.
     * @param String $content Il contenuto dell'articolo.
     * @return Boolean Se l'articolo è stato creato oppure no.
     */
    public static function insert($user_id, $title, $image, $content)
    {
        $title = htmlspecialchars($title);

        /**
         * ATTENZIONE: Questa funzione non è adatta per proteggere da attacchi XSS.
         *             È quindi possibile inserire del codice JavaScript malevolo,
         *             all'interno del contenuto di un articolo.
         *             Riferimento: https://www.php.net/manual/en/function.strip-tags.php
         */
        $content = strip_tags($content, '<h1><ul><li><a><br><img><code>');

        if (empty($title) || empty($content)) {
            $_SESSION["article_error"] = "Il titolo o il contenuto non possono essere vuoti!";
            return false;
        }

        if (strlen($title) > 255) {
            $_SESSION["article_error"] = "Il titolo non può essere più lungo di 255 caratteri!";
            return false;
        }

        if (strlen($content) > 2000) {
            $_SESSION["article_error"] = "Il contenuto dell'articolo non può superare i 2000 caratteri!";
            return false;
        }
        $file_name = null;
        if ($image != null) {
            $tmp_path = $image["tmp_name"];
            $file_name = hash("sha256", file_get_contents($tmp_path));
            if (!move_uploaded_file($tmp_path, __DIR__.'/../../storage/'.$file_name)) {
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
        if (!$query) {
            $_SESSION["article_error"] = "Impossibile inserire l'articolo!";
            return false;
        }
        return true;
    }

    /**
     * Metodo che permette di eliminare un articolo.
     *
     * @param Integer $article_id L'id dell'articolo.
     * @return Boolean Se l'articolo è stato eliminato oppure no.
     */
    public static function delete($article_id)
    {
        $article = self::getById($article_id);
        if ($article) {
            $query = Database::get()->prepare("DELETE FROM articles WHERE id = :article_id");
            $query->bindParam(":article_id", $article_id);
            $query->execute();
            if (!$query) {
                $_SESSION["error"] = "Impossibile eliminare l'articolo!";
                return false;
            }
            
            /**
             * Ricavo solamente il nome del file per essere sicuro di non eliminare nulla
             * al di fuori della cartella storage.
             */
            $file = basename($article["image"]);
            $path = __DIR__.'/../../storage/'.$file;
            if (file_exists($path)) {
                unlink($path);
            }

            $_SESSION["success"] = "Articolo eliminato!";
            return true;
        }
        return false;
    }
}
