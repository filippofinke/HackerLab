<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {


    $login_middleware = function ($request, $response, $next) {
        if (!isset($_SESSION["user"])) {
            $_SESSION["big_error"] = "Per eseguire questa azione devi accedere!";
            return $response->withRedirect("/", 302);
        }
        $response = $next($request, $response);
        return $response;
    };

    /**
     * Chiamate
     */

    $app->get('/logout', function (Request $request, Response $response, array $args) use ($app) {
        unset($_SESSION["user"]);
        session_destroy();
        setcookie("permission", '', time() - 3600);
        return $response->withRedirect("/", 302);
    });

    $app->post('/login', function (Request $request, Response $response, array $args) use ($app) {
        $email = $request->getParam('email');
        $password = $request->getParam('password');
        Users::login($email, $password);
        return $response->withRedirect("/", 302);
    });

    /**
     * Pagine
     */
    $app->get('/[page/{page}]', function (Request $request, Response $response, array $args) use ($app) {
        $permission = isset($_COOKIE["permission"])?base64_decode($_COOKIE["permission"]):null;
        $page = $args["page"] ?? 0;
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
     * Pagine che richiedono l'accesso.
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

    $app->get('/admin/articoli', function (Request $request, Response $response, array $args) use ($app) {
        $this->view->render($response, "admin/articoli.phtml", $args);
    });

    $app->get('/admin/utenti', function (Request $request, Response $response, array $args) use ($app) {
        $this->view->render($response, "admin/utenti.phtml", $args);
    });

};
