<?php

namespace App\Model;

use Slim\Http\Response;
use Slim\Http\ServerRequest;
use App\Database\DatabaseObject;
use App\Utility\Time;


class User extends DatabaseObject
{

    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $username;
    public $password;
    public $profile_pic;
    public $about_me;
    public $created_at;

    // public $session = false;

    protected static $dbColumns = [
        "id",
        "first_name",
        "last_name",
        "email",
        "username",
        "password",
        "profile_pic",
        "about_me",
        "created_at"
    ];

    protected static $tableName = "user";

    public function __construct($argsArray = [])
    {
        $this->first_name = $argsArray["first_name"] ?? "";
        $this->last_name = $argsArray["last_name"] ?? "";
        $this->email = $argsArray["email"] ?? "";
        $this->username = $argsArray["username"] ?? "";
        $this->password = $argsArray["password"] ?? "";
        $this->profile_pic = $argsArray["profile_pic"] ?? "";
        $this->about_me = $argsArray["about_me"] ?? "";
        $this->created_at = Time::getCurrentTime();
    }

    private function output($argsObject)
    {
        $output = [];
        $output["id"] = $argsObject->id;
        $output["first_name"] = $argsObject->first_name;
        $output["last_name"] = $argsObject->last_name;
        $output["email"] = $argsObject->email;
        $output["username"] = $argsObject->username;
        $output["password"] = $argsObject->password;
        $output["profile_pic"] = $argsObject->profile_pic;
        $output["about_me"] = $argsObject->about_me;
        $output["created_at"] = $argsObject->created_at;
        return $output;
    }

    private function postOutput($argsObject)
    {
        $output = [];
        $output['id']           = $argsObject->id;
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

        $users = User::pagination();


        if ($users) {
            $output = [];
            $users["rowsPerPage"];
            $users["totalResults"];
            $users["totalPages"];
            $users["has_next_page"];
            $users["has_previous_page"];
            $users["data"];

            foreach ($users["data"] as $user) {
                $output[] = $this->output($user);
            }

            $payload = [];
            $payload["rowsPerPage"] = $users["rowsPerPage"];
            $payload["totalResult"] = $users["totalResults"];
            $payload["totalPages"] = $users["totalPages"];
            $payload["has_next_page"] = $users["has_next_page"];
            $payload["has_previous_page"] = $users["has_previous_page"];
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

        if (array_key_exists("user_id", $args)) {
            $user = User::findById($args["user_id"]);

            if ($user) {
                $userPosts = Post::findByUserId($user->id);
                $totalPost = count((array)$userPosts);
                if (empty($userPosts)) {
                    $output = [
                        "totalPost" => $totalPost,
                        "post" => "no post"
                    ];
                    $payload = [];
                    $payload["totalResult"] = 1;
                    $payload["results"] = [
                        "userDetails" => $this->output($user),
                        "userPost" => $output
                    ];
                    return $response->withStatus(400)->withJson($payload);
                } else {

                    $postOutput = [];
                    foreach ($userPosts as $userPost) {
                        $postOutput[] = $this->postOutput($userPost);
                    }
                    $output = [
                        "totalPost" => $totalPost,
                        "post" => $postOutput
                    ];
                    $payload = [];
                    $payload["totalResult"] = 1;
                    $payload["results"] = [
                        "userDetails" => $this->output($user),
                        "userPost" => $output
                    ];

                    return $response->withStatus(200)->withJson($payload);
                }
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
        $imageToSave = "";
        if (isset($_FILES['profile_pic'])) {
            $errors = array();
            $file_name = $_FILES['profile_pic']['name'];
            $file_size = $_FILES['profile_pic']['size'];
            $file_tmp = $_FILES['profile_pic']['tmp_name'];
            $file_type = $_FILES['profile_pic']['type'];

            if ($file_type != "image/jpeg") {
                $errors[] = "extension not allowed, please choose a JPEG or PNG file.";
                return $response->withStatus(404)->withJson($errors);
            }

            if ($file_size > 8097152) {
                $errors[] = 'File size must be excately 8 MB';
                return $response->withStatus(404)->withJson($errors);
            }

            if (empty($errors) == true) {
                move_uploaded_file($file_tmp, "../assets/profile_pic/" . $file_name);
                $imageToSave = "http://localhost/phpfun/api/playground/myAPIs/blogTest/v2/assets/profile_pic/" . $file_name;
            } else {
                return $response->withStatus(404)->withJson($errors);
            }
        }
        $argsArray["first_name"] = $request->getParsedBodyParam("first_name");
        $argsArray["last_name"] = $request->getParsedBodyParam("last_name");
        $argsArray["email"] = $request->getParsedBodyParam("email");
        $argsArray["username"] = $request->getParsedBodyParam("username");
        $argsArray["password"] = $request->getParsedBodyParam("password");
        $argsArray["profile_pic"] = $imageToSave;
        $argsArray["about_me"] = $request->getParsedBodyParam("about_me");
        $argsArray["created_at"] = Time::getCurrentTime();

        $emailCheck = User::checkUserEmail($argsArray["email"]);
        if($emailCheck == true) {
            $payload = ["message" => "email alredy exist"];
            return $response->withStatus(400)->withJson($payload);
            exit;
        }

        $user = new User($argsArray);
        $result = $user->save();
        $id = $user->id;
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
        $user = User::findById($id);

        if (!$user) {
            return $response->withStatus(401)->withJson(['message' => 'Failed: ID Not Found', 'details' => $id]);
        }


        $argsArray = [];
        $argsArray["first_name"] = $request->getParsedBodyParam("first_name");
        $argsArray["last_name"] = $request->getParsedBodyParam("last_name");
        $argsArray["email"] = $request->getParsedBodyParam("email");
        $argsArray["username"] = $request->getParsedBodyParam("username");
        $argsArray["password"] = $request->getParsedBodyParam("password");
        $argsArray["profile_pic"] = $user->profile_pic;
        $argsArray["about_me"] = $request->getParsedBodyParam("about_me");
        $argsArray["created_at"] = $user->created_at;

        $emailCheck = User::checkUserEmail($argsArray["email"]);
        if ($emailCheck == true) {
            $argsArray["email"] = $user->email;
        }

        $user->mergeAttributes($argsArray);
        $result = $user->save();

        if ($result == true) {
            $payload = [];
            $payload['totalResults'] = 1;
            $payload['results'] = $this->output($user);

            return $response->withStatus(200)->withJson($payload);
        } else {
            /**
             * Probably 'form-data' was used in the 'content-type' header instead of 'x-www-form-urlencoded'. 'form-data' wont work for PUT
             * request on slim.
             */

            $payload = ['message' => 'email already used'];
            return $response->withStatus(400)->withJson($payload);
        }
    }

    public function deleteRoute(ServerRequest $request, Response $response, array $args)
    {
        $id = $args["id"];

        $user = User::findById($id);

        if (!$user) {
            $output = ["id" => $id];
            $payload = [
                "message" => "Failed: Id not found",
                "output" => $output
            ];
            return $response->withStatus(401)->withJson($payload);
        }

        if ($user->delete()) {
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

    public static function userLogin(ServerRequest $request, Response $response, array $args)
    {
        $email = $request->getQueryParam("email");
        $password = $request->getQueryParam("password");
        $users = User::login($email, $password);
        foreach($users as $user) {
            $session_id = $user->id;
        }
        session_start();
        static::$session = true;
        $_SESSION["user_id"] = $session_id;
        $payload["session_state"] = static::$session;
        $payload["session_id"] = $_SESSION["user_id"];
        $payload["message"] = "login successful";
        return $response->withStatus(200)->withJson($payload);
    }
}
