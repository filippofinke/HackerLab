<?php
/**
 * Filippo Finke
 * Routes
 * 
 * Questo file contiene tutti i percorsi e la logica di essi.
 */
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {

    /**
     * Funzioni di aiuto.
     */

     /**
      * Middleware che verifica se un utente è admin oppure no.
      */
    $admin_middleware = function ($request, $response, $next) {
        if (!isset($_SESSION["user"])) {
            $_SESSION["big_error"] = "Per eseguire questa azione devi aver eseguito l'accesso! <a href='#login'>Accedi!</a>";
            return $response->withRedirect("/", 302);
        }
        if($_SESSION["user"]["permission"] != "administrator") {
            $_SESSION["big_error"] = "Non hai i permessi per accedere a questa pagina!";
            return $response->withRedirect("/", 302);
        }
        $response = $next($request, $response);
        return $response;
    };

    /**
     * Middleware che verifica se un utente ha eseguito l'accesso oppure no.
     */
    $login_middleware = function ($request, $response, $next) {
        if (!isset($_SESSION["user"])) {
            $_SESSION["big_error"] = "Per eseguire questa azione devi aver eseguito l'accesso! <a href='#login'>Accedi!</a>";
            return $response->withRedirect("/", 302);
        }
        $response = $next($request, $response);
        return $response;
    };

    /**
     * Chiamate dirette.
     */

     $app->get('/reset', function (Request $request, Response $response, array $args)  use ($app) {
        if (Database::reset()) {
            $_SESSION["success"] = "Database ripristinato con successo!";
        } else {
            $_SESSION["error"] = "Impossibile ripristinare il database!";
        }
        return $response->withRedirect("/logout", 302);
    });

    /**
     * Percorso /image/
     * Parametri GET:
     *  file_name = immagine
     * 
     * Utilizzato per ricavare le immagini degli articoli.
     * ATTENZIONE: Questo percorso è vulnerabile all'attacco Directory traversal o File Inclusion Vulnerability!
     */
    $app->get('/image/', function (Request $request, Response $response, array $args) use ($app) {
        $file_name = $request->getParam('file_name');
        $file_path = __DIR__.'/../storage/'.$file_name;
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $response->write($content);
            return $response->withHeader('Content-Type', FILEINFO_MIME_TYPE);
        }
        $response = new \Slim\Http\Response(404);
        return $response->write("Immagine inesistente.");
    });

    /**
     * Percorso /logout
     * 
     * Permette ad un utente di eliminare la propria sessione.
     */
    $app->get('/logout', function (Request $request, Response $response, array $args) use ($app) {
        unset($_SESSION["user"]);
        setcookie("permission", '', time() - 3600);
        return $response->withRedirect("/", 302);
    });

    /**
     * Percorso /reset
     * Parametri POST:
     *  email = email alla quale inviare il link di recupero password.
     * 
     * Utilizzato per inviare email di recupero password.
     * ATTENZIONE: Questo percorso è vulnerabile all'attacco Session Prediction!
     */
    $app->post('/reset', function (Request $request, Response $response, array $args) use ($app) {
        $email = $request->getParam('email');
        Users::generateResetToken($email);
        return $response->withRedirect("/", 302);
    });

    /**
     * Percorso /login
     * Parametri POST:
     *  email = email dell'utente.
     *  password = password dell'utente.
     * 
     * Utilizzato per autenticare un utente.
     */
    $app->post('/login', function (Request $request, Response $response, array $args) use ($app) {
        $email = $request->getParam('email');
        $password = $request->getParam('password');
        Users::login($email, $password);
        return $response->withRedirect("/", 302);
    });

    /**
     * Percorso /reset_password
     * Parametri POST:
     *  reset_token = il token di recupero password.
     *  password = la nuova password.
     *  repeat_password = la nuova password ripetuta.
     * 
     * Utilizzato per impostare una password attraverso il token di recupero.
     */
    $app->post('/reset_password', function (Request $request, Response $response, array $args) use ($app) {
        $reset_token = $request->getParam('reset_token');
        $password = $request->getParam('password');
        $repeat_password = $request->getParam('repeat_password');
        if(!Users::resetPassword($reset_token, $password, $repeat_password)) {
            return $response->withRedirect("/?reset_token=".$reset_token,302);
        } else {
            return $response->withRedirect("/",302);
        }
    });

    /**
     * Percorso /register
     * Parametri POST:
     *  full_name = nome completo dell'utente.
     *  email = l'email dell'utente.
     *  password = la password.
     *  repeat_password = la password ripetuta.     
     * 
     * Utilizzato per la creazione di un utente.
     * */
    $app->post('/register', function (Request $request, Response $response, array $args) use ($app) {
        $full_name = $request->getParam('full_name');
        $email = $request->getParam('email');
        $password = $request->getParam('password');
        $repeat_password = $request->getParam('repeat_password');
        Users::register($full_name, $email, $password, $repeat_password);
        return $response->withRedirect("/register", 302);
    });

    /**
     * Pagine senza autenticazione.
     */

    /**
     * Percorso  / o /page/{page}
     * 
     * Pagina principale, vengono mostrati gli utili articoli.
     */
    $app->get('/[page/{page}]', function (Request $request, Response $response, array $args) use ($app) {
        $permission = isset($_COOKIE["permission"])?base64_decode($_COOKIE["permission"]):null;
        $page = $args["page"] ?? 0;
        if(!is_numeric($page)) $page = 0;
        $articles = [];
        if($request->getParam('search') !== null) {
            $articles = Articles::search($request->getParam('search'), $page);
        } else {
            $articles = Articles::get($page);
        }
        if(count($articles) == 0 && $page != 0) {
            return $response->withRedirect("/page/".($page - 1), 302);
        }
        $this->view->render($response, "index.phtml", array(
            'permission' => $permission,
            'articles' => $articles,
            'page' => $page
        ));
    });
    
    /**
     * Pagina di registrazione.
     */
    $app->get('/register', function (Request $request, Response $response, array $args) use ($app) {
        if(isset($_SESSION["user"])) return $response->withRedirect("/", 302);
        $this->view->render($response, "register.phtml", $args);
    });

    /**
     * Pagine con autenticazione.
     */

    /**
     * Percorso /profilo o /profilo/{user_id}
     * 
     * Pagina di profilo di un utente.
     */
    $app->get('/profile[/{user_id}]', function (Request $request, Response $response, array $args) use ($app) {
        $permission = isset($_COOKIE["permission"])?base64_decode($_COOKIE["permission"]):null;
        $user_id = $args["user_id"] ?? $_SESSION["user"]["id"];
        $user = Users::getById($user_id);
        if ($user) {
            $articles = Articles::getByUserId($user_id);
            $this->view->render($response, "profile.phtml", array(
                'permission' => $permission,
                'user' => $user,
                'articles' => $articles
            ));
        } else {
            // Lancia un errore in caso l'utente non sia esistente.
            throw new Exception("User not found");
        }
    })->add($login_middleware);

    /**
     * Percorso /post/{post_id}
     * 
     * Pagina di un articolo.
     */
    $app->get('/post/{post_id}', function (Request $request, Response $response, array $args) use ($app) {
        $permission = isset($_COOKIE["permission"])?base64_decode($_COOKIE["permission"]):null;
        $post = Articles::getById($args["post_id"]);
        if ($post) {
            $this->view->render($response, "post.phtml", array(
                'permission' => $permission,
                'post' => $post,
                'comments' => Comments::getByArticleId($post["id"])
            ));
        } else {
            return $response->withRedirect("/", 302);
        }
    })->add($login_middleware);

    /**
     * Percorso /post/{post_id}
     * Parametri POST:
     *  comment = il commento.
     * 
     * Utilizzato per aggiungere un commento ad un articolo.
     */
    $app->post('/post/{post_id}', function (Request $request, Response $response, array $args) use ($app) {
        $comment = $request->getParam("comment");
        $post = Articles::getById($args["post_id"]);
        if ($post) {
            if (preg_replace('/\s+/', '', $comment) != "") {
                Comments::insert($post["id"], $_SESSION["user"]["id"], $comment);
            } else {
                $_SESSION["error"] = "Il commento non può essere vuoto!";
            }
            return $response->withRedirect("/post/".$args["post_id"], 302);
        }
        return $response->withRedirect("/", 302);
    })->add($login_middleware);

    /**
     * Percorso /post
     * Parametri POST:
     *  title = il titolo.
     *  content = il contenuto.
     * Altri parametri:
     *  image = il file dell'immagine
     * 
     * Utilizzato per la creazione di un articolo.
     */
    $app->post('/post', function (Request $request, Response $response, array $args) use ($app) {
        
        $title = $request->getParam('title');
        $content = $request->getParam('content');
        $image = null;
        if(isset($_FILES['image']) && $_FILES['image']['name'] != "") $image = $_FILES['image'];
        if(!Articles::insert($_SESSION["user"]["id"], $title, $image, $content)) {
            return $response->withRedirect("/?publish_article=true", 302);
        }
        return $response->withRedirect("/", 302);

    })->add($login_middleware);

    /**
     * Percorso /admin/articles o /admin/articles/{page}
     * 
     * Pagina di amministrazione degli articoli.
     */
    $app->get('/admin/articles[/{page}]', function (Request $request, Response $response, array $args) use ($app) {
        $page = $args["page"] ?? 0;
        if(!is_numeric($page)) $page = 0;

        $articles = Articles::get($page);
        if(count($articles) == 0 && $page != 0) {
            return $response->withRedirect("/admin/articles/".($page - 1), 302);
        }

        $this->view->render($response, "admin/articoli.phtml", array(
            'page' => $page,
            'articles' => $articles
        ));
    })->add($admin_middleware);

    /**
     * Percorso /articles/delete/{article_id}
     * 
     * Utilizzato per eliminare un articolo.
     */
    $app->get('/articles/delete/{article_id}', function (Request $request, Response $response, array $args) use ($app) {
        Articles::delete($args["article_id"]);
        return $response->withRedirect("/admin/articles", 302);
    })->add($admin_middleware);

    /**
     * Percorso /admin/users
     * 
     * Pagina di amministrazione utenti.
     */
    $app->get('/admin/users', function (Request $request, Response $response, array $args) use ($app) {
        $this->view->render($response, "admin/utenti.phtml", array(
            'users' => Users::get()
        ));
    })->add($admin_middleware);

     /**
     * Percorso /users/delete/{user_id}
     * 
     * Utilizzato per eliminare un utente.
     */
    $app->get('/users/delete/{user_id}', function (Request $request, Response $response, array $args) use ($app) {
        Users::delete($args["user_id"]);
        return $response->withRedirect("/admin/users", 302);
    })->add($admin_middleware);

    /**
     * Percorso /users/disable/{user_id}
     * 
     * Utilizzato per disabilitare un utente.
     */
    $app->get('/users/disable/{user_id}', function (Request $request, Response $response, array $args) use ($app) {
        Users::disable($args["user_id"]);
        return $response->withRedirect("/admin/users", 302);
    })->add($admin_middleware);

    /**
     * Percorso /users/enable/{user_id}
     * 
     * Utilizzato per abilitare un utente.
     */
    $app->get('/users/enable/{user_id}', function (Request $request, Response $response, array $args) use ($app) {
        Users::enable($args["user_id"]);
        return $response->withRedirect("/admin/users", 302);
    })->add($admin_middleware);

};
