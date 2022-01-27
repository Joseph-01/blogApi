<?php

namespace App\Model;

use Slim\Http\ServerRequest;
use Slim\Http\Response;
use App\Database\DatabaseObject;
use App\Model\User;
use App\Utility\Time;


class Likes extends DatabaseObject
{
    public $id;
    public $user_id;
    public $post_id;
    public $likes;
    public $created_at;

    protected static $dbColumns = [
        "id",
        "post_id",
        "user_id",
        "likes",
        "created_at"
    ];

    protected static $tableName = "likes";

    public function __construct($argsArray = [])
    {
        $this->post_id = $argsArray["post_id"] ?? "";
        $this->user_id = $argsArray["user_id"] ?? "";
        $this->likes = $argsArray["likes"] ?? "";
        $this->created_at = Time::getCurrentTime();
    }

    private function likesOutput($argsObject)
    {
        $output = [];
        $output['id'] = $argsObject->id;
        $output['post_id'] = $argsObject->post_id;
        $output['user_id'] = $argsObject->user_id;
        $output['likes'] = $argsObject->likes;
        $output['created_at'] = $argsObject->created_at;
        return $output;
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
        // $parsedBody = $request->getParsedBody();
        $checkLikes = Likes::checkLikes($post_id, $user_id);
        
        if ($checkLikes == true) {
            return $response->withStatus(200)->withJson(["message" => "already liked post"]);
        }

        $argsArray = [];
        $argsArray["post_id"] = $post_id;
        $argsArray["user_id"] = $user_id;
        $argsArray["likes"] = 1;
        $argsArray["created_at"] = Time::getCurrentTime();
        $like = new Likes($argsArray);
        $result = $like->save();
        $id = $like->id;
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