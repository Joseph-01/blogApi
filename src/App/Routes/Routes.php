<?php

use App\Model\Comment;
use App\Model\Likes;
use App\Model\Post;
use App\Model\User;
use Slim\App;

return function(App $app) {

    //routes for post class to CRUD articles
    $app->get("/articles", Post::class . ":get");
    $app->get("/articles/{id}", Post::class . ":get");
    $app->post("/articles", Post::class . ":post");
    $app->put("/articles/{id}", Post::class . ":put");
    $app->delete("/articles/{id}", Post::class . ":deleteRoute");

    //routes for user class to CRUD users
    $app->get("/users", User::class . ":get");
    $app->get("/users/articles/{user_id}", User::class . ":get");
    $app->get("/users/login", User::class . ":userLogin");
    $app->post("/users", User::class . ":post");
    $app->put("/users/{id}", User::class . ":put");
    $app->delete("/users/{id}", User::class . ":deleteRoute");

    //routes for likes CRUD
    $app->post("/likes[/{post_id}[/{user_id}]]", Likes::class . ":post");

    //routes for comment CRUD
    $app->post("/comments[/{post_id}[/{user_id}]]", Comment::class . ":post");
    

};