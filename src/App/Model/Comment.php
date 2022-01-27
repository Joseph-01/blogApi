<?php

namespace App\Model;

use Slim\Http\ServerRequest;
use Slim\Http\Response;
use App\Database\DatabaseObject;
use App\Model\User;
use App\Utility\Time;

class Comment extends DatabaseObject
{
    public $id;
    public $post_id;
    public $user_id;
    public $comment;
    public $created_at;

    protected static $dbColumns = [
        "id",
        "post_id",
        "user_id",
        "comment",
        "created_at"
    ];

    protected static $tableName = "comment";

    public function __construct($argsArray = [])
    {
        $this->post_id = $argsArray["post_id"] ?? "";
        $this->user_id = $argsArray["user_id"] ?? "";
        $this->comment = $argsArray["comment"] ?? "";
        $this->created_at = Time::getCurrentTime();
    }

    /**
     * @param ServerRequest Request object which you can inspect and manipulate the HTTP request method, headers, and body.
     * @param Response Response object which you can inspect and manipulate the HTTP response status, headers, and body.
     * @param Array The third argument is an associative array that contains values for the current route’s named placeholders
     * @return Response which implements Response Returns HTTP response with status code
     */
    public function post(ServerRequest $request, Response $response, array $args)
    {
        $post_id = $args["post_id"];
        $user_id = $args["user_id"];

        $argsArray = [];
        $argsArray["post_id"] = $post_id;
        $argsArray["user_id"] = $user_id;
        $argsArray["comment"] = $request->getParsedBodyParam("comment");
        $argsArray["created_at"] = Time::getCurrentTime();
        $comment = new Comment($argsArray);
        $result = $comment->save();
        $id = $comment->id;
        if ($result) {
            return $response->withStatus(201)->withJson([
                "Message" => "created",
                "id" => $id
            ]);
        } else {
            // Failed
            $payload = ['message' => 'Failed: POST operation'];
            return $response->withStatus(400)->withJson($payload);
        }
    }
}