<?php
require "vendor/autoload.php";
require_once "src/App/Database/Database.php";
require_once "src/App/Database/DatabaseObject.php";
require_once "src/App/Model/Post.php";
require_once "src/App/Model/User.php";
require_once "src/App/Model/Likes.php";
require_once "src/App/Model/Comment.php";
require_once "src/App/Utility/Time.php";
$routes = require "src/App/Routes/Routes.php";

use Slim\Factory\AppFactory;
use App\Database\Database;
use App\Database\DatabaseObject;
use App\Model\Post;
use App\Model\User;
use App\Utility\Time;

$app = AppFactory::create();

//basepath for heruko
$app->setBasePath('/public');

//basepath for local development
//$app->setBasePath('/phpfun/api/playground/myAPIs/blogTest/v2/blogApiTest/public');

$database = new Database;
DatabaseObject::setDatabase($database);
$routes($app);


$app->run();