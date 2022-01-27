<?php

namespace App\Model;

use Slim\Http\Response;
use App\Database\DatabaseObject;
use App\Model\User;
use App\Model\Likes;
use App\Utility\Time;
use Slim\Http\ServerRequest;

class Post extends DatabaseObject
{
    public $id;
    public $user_id;
    public $title;
    public $body;
    public $image;
    public $caption;
    public $category;
    public $created_at;
    public $updated_at;

    protected static $dbColumns = [
        "id",
        "user_id",
        "title",
        "body",
        "image",
        "caption",
        "category",
        "created_at",
        "updated_at"
    ];
    protected static $tableName = "post";

    public function __construct($argsArray = [])
    {
        $this->user_id      = $argsArray["user_id"] ?? "";
        $this->title        = $argsArray["title"] ?? "";
        $this->body         = $argsArray["body"] ?? "";
        $this->image        = $argsArray["image"] ?? "";
        $this->caption      = $argsArray["caption"] ?? "";
        $this->category     = $argsArray["category"] ?? "";
        $this->created_at   = $argsArray["created_at"] ?? Time::getCurrentTime();
        $this->updated_at   = $argsArray["updated_at"] ?? Time::getCurrentTime();
    }

    /**
     * @param argsObject recieves class instance and put them into an array
     * @return output [type] array
     */
    private function output($argsObject)
    {
        $user = User::findById($argsObject->user_id);
        $output = [];
        $output['id']           = $argsObject->id;
        $output['user_id']      = $argsObject->user_id;
        $output['full_name']    = $user->first_name . " " . $user->last_name;
        $output['title']        = $argsObject->title;
        $output['body']         = $argsObject->body;
        $output['image']        = $argsObject->image;
        $output['caption']      = $argsObject->caption;
        $output['category']     = $argsObject->category;
        $output['created_at']   = $argsObject->created_at;
        $output['updated_at']   = $argsObject->updated_at;
        return $output;
    }



    /**
     * @param ServerRequest Request object which you can inspect and manipulate the HTTP request method, headers, and body.
     * @param Response Response object which you can inspect and manipulate the HTTP response status, headers, and body.
     * @param Array The third argument is an associative array that contains values for the current route’s named placeholders
     * @return Response Returns HTTP response with status code
     */
    public function get(ServerRequest $request, Response $response, array $args)
    {

        // get uri query parameters
        $queryParams = $request->getQueryParams();
        // checks if the @Variable $queryParams & $args are empty, if yes then it must be a 'getAll$user' request
        if (!empty($args)) {
            /**
             * Now we are sure we either have a 'queryParam' or 'uri path arguments'. The next line of code checks if the 'args' is empty
             * if yes, then we know its a 'queryParam' but if no, its 'args' because we cant have 'queryParams' and 'args' in same request
             * in line with the API DESIGN.
             */
            return $this->handleRequestArgs($request, $response, $args);
        }

        $posts = Post::pagination();


        if ($posts) {
            $output = [];
            $posts["rowsPerPage"];
            $posts["totalResults"];
            $posts["totalPages"];
            $posts["has_next_page"];
            $posts["has_previous_page"];
            $posts["data"];

            foreach ($posts["data"] as $post) {
                $output[] = $this->output($post);
            }

            $payload = [];
            $payload["rowsPerPage"] = $posts["rowsPerPage"];
            $payload["totalResult"] = $posts["totalResults"];
            $payload["totalPages"] = $posts["totalPages"];
            $payload["has_next_page"] = $posts["has_next_page"];
            $payload["has_previous_page"] = $posts["has_previous_page"];
            $payload['results'] = $output;
            return $response->withStatus(200)->withJson($payload);
        }
    }




    private function handleRequestArgs(ServerRequest $request, Response $response, array $args)
    {
        /**
         * A little Validation here...
         * Checks if the specified queryParams is supplied and if the values are numeric
         */
        if (count($args) > 2 || (count($args) !== count(array_filter($args, 'is_numeric')))) {
            $payload = ['message' => 'Failed: wrong query parameters'];
            return $response->withStatus(400)->withJson($payload);
        }

        if (array_key_exists('id', $args)) {
            $post = Post::findById($args["id"]);
            if ($post) {
                $likes = Likes::findByPostId($post->id);
                $comments = Comment::findByPostId($post->id);
                $output = [];
                $output = [
                    "post" => $post,
                    "likes" => count((array)$likes),
                    "commentCount" => count((array)$comments),
                    "comment" => $comments
                ];
                $payload = [];
                $payload['totalResults'] = 1;
                $payload['results'] = $output;
                return $response->withStatus(200)->withJson($payload);
            } else {
                $payload = ['message' => 'Failed: No Resource'];
                return $response->withStatus(404)->withJson($payload);
            }
        }
    }

    /**
     * @param ServerRequest Request object which you can inspect and manipulate the HTTP request method, headers, and body.
     * @param Response Response object which you can inspect and manipulate the HTTP response status, headers, and body.
     * @param Array The third argument is an associative array that contains values for the current route’s named placeholders
     * @return Response which implements Response Returns HTTP response with status code
     */
    public function post(ServerRequest $request, Response $response, array $args)
    {
        $argsArray = [];

        if (isset($_FILES['image'])) {
            $errors = array();
            $file_name = $_FILES['image']['name'];
            $file_size = $_FILES['image']['size'];
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_type = $_FILES['image']['type'];

            if ($file_type != "image/jpeg") {
                $errors[] = "extension not allowed, please choose a JPEG or PNG file.";
                return $response->withStatus(404)->withJson($errors);
            }

            if ($file_size > 8097152) {
                $errors[] = 'File size must be excately 8 MB';
                return $response->withStatus(404)->withJson($errors);
            }

            if (empty($errors) == true) {
                move_uploaded_file($file_tmp, "../assets/images/" . $file_name);
                $imageToSave = "http://localhost/phpfun/api/playground/myAPIs/blogTest/v2/assets/images/" . $file_name;
            } else {
                return $response->withStatus(404)->withJson($errors);
            }
        }
        $argsArray["user_id"] = $request->getParsedBodyParam("user_id");
        $argsArray["title"] = $request->getParsedBodyParam("title");
        $argsArray["body"] = $request->getParsedBodyParam("body");
        $argsArray["image"] = $imageToSave;
        $argsArray["caption"] = $request->getParsedBodyParam("caption");
        $argsArray["category"] = $request->getParsedBodyParam("category");
        $argsArray["created_at"] = Time::getCurrentTime();
        $argsArray["updated_at"] = Time::getCurrentTime();


        $post = new Post($argsArray);
        $result = $post->save();
        $id = $post->id;
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

    /**
     * @param ServerRequest Request object which you can inspect and manipulate the HTTP request method, headers, and body.
     * @param Response Response object which you can inspect and manipulate the HTTP response status, headers, and body.
     * @param Array The third argument is an associative array that contains values for the current route’s named placeholders
     * @return Response Returns HTTP response with status code
     */
    public function put(ServerRequest $request, Response $response, array $args)
    {
        $id = $args["id"];
        $post = Post::findById($id);

        if (!$post) {
            return $response->withStatus(401)->withJson(['message' => 'Failed: ID Not Found', 'details' => $id]);
        }


        $argsArray = [];
        $argsArray["user_id"] = $post->user_id;
        $argsArray["title"] = $request->getParsedBodyParam("title");
        $argsArray["body"] = $request->getParsedBodyParam("body");
        $argsArray["image"] = $post->image;
        $argsArray["caption"] = $request->getParsedBodyParam("caption");
        $argsArray["category"] = $request->getParsedBodyParam("category");
        $argsArray["created_at"] = $post->created_at;
        $argsArray["updated_at"] = Time::getCurrentTime();


        $post->mergeAttributes($argsArray);
        $result = $post->save();

        if ($result == true) {
            $payload = [];
            $payload['totalResults'] = 1;
            $payload['results'] = $this->output($post);

            return $response->withStatus(200)->withJson($payload);
        } else {
            /**
             * Probably 'form-data' was used in the 'content-type' header instead of 'x-www-form-urlencoded'. 'form-data' wont work for PUT
             * request on slim.
             */

            $payload = ['message' => 'Failed: use x-www-form-urlencoded in content-type header'];
            return $response->withStatus(400)->withJson($payload);
        }
    }

    public function deleteRoute(ServerRequest $request, Response $response, array $args)
    {
        $id = $args["id"];

        $post = Post::findById($id);

        if (!$post) {
            $output = ["id" => $id];
            $payload = [
                "message" => "Failed: Id not found",
                "output" => $output
            ];
            return $response->withStatus(401)->withJson($payload);
        }

        if ($post->delete()) {
            // Success
            $output = ['id' => $id];
            $payload = ['message' => 'Deleted Successfully!', 'details' => $output];
            return $response->withStatus(200)->withJson($payload);
        } else {
            // Failed
            $payload = ['message' => 'Failed: DELETE operation'];
            return $response->withStatus(400)->withJson($payload);
        }
    }
}
