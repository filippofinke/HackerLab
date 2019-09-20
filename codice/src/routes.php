<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {

    /**
     * Funzioni di aiuto.
     */

    $admin_middleware = function ($request, $response, $next) {
        if (!isset($_SESSION["user"])) {
            $_SESSION["big_error"] = "Per eseguire questa azione devi aver eseguito l'accesso!";
            return $response->withRedirect("/", 302);
        }
        if($_SESSION["user"]["permission"] != "administrator") {
            $_SESSION["big_error"] = "Non hai i permessi per accedere a questa pagina!";
            return $response->withRedirect("/", 302);
        }
        $response = $next($request, $response);
        return $response;
    };

    $login_middleware = function ($request, $response, $next) {
        if (!isset($_SESSION["user"])) {
            $_SESSION["big_error"] = "Per eseguire questa azione devi aver eseguito l'accesso!";
            return $response->withRedirect("/", 302);
        }
        $response = $next($request, $response);
        return $response;
    };

    /**
     * Chiamate dirette.
     */

     /**
      * VULNERABILE!
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

    $app->get('/logout', function (Request $request, Response $response, array $args) use ($app) {
        unset($_SESSION["user"]);
        session_destroy();
        setcookie("permission", '', time() - 3600);
        return $response->withRedirect("/", 302);
    });

    $app->post('/reset', function (Request $request, Response $response, array $args) use ($app) {
        $email = $request->getParam('email');
        Users::generateResetToken($email);
        return $response->withRedirect("/", 302);
    });

    $app->post('/login', function (Request $request, Response $response, array $args) use ($app) {
        $email = $request->getParam('email');
        $password = $request->getParam('password');
        Users::login($email, $password);
        return $response->withRedirect("/", 302);
    });

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
    
    $app->get('/register', function (Request $request, Response $response, array $args) use ($app) {
        if(isset($_SESSION["user"])) return $response->withRedirect("/", 302);
        $this->view->render($response, "register.phtml", $args);
    });

    /**
     * Pagine con autenticazione.
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
            return $response->withRedirect("/", 302);
        }
    })->add($login_middleware);

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

    $app->get('/articles/delete/{article_id}', function (Request $request, Response $response, array $args) use ($app) {
        Articles::delete($args["article_id"]);
        return $response->withRedirect("/admin/articles", 302);
    })->add($admin_middleware);

    $app->get('/admin/users', function (Request $request, Response $response, array $args) use ($app) {
        $this->view->render($response, "admin/utenti.phtml", array(
            'users' => Users::get()
        ));
    })->add($admin_middleware);

    $app->get('/users/delete/{user_id}', function (Request $request, Response $response, array $args) use ($app) {
        Users::delete($args["user_id"]);
        return $response->withRedirect("/admin/users", 302);
    })->add($admin_middleware);

};
