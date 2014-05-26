<?php
require __DIR__.'/../framework/Routing/RouteCollection.php';
require __DIR__.'/../framework/Routing/Router.php';
require __DIR__.'/../framework/Routing/Route.php';
require __DIR__.'/../framework/Routing/RouteCompiler.php';
require __DIR__.'/../framework/View/View.php';
require __DIR__.'/../framework/Session/SessionInterface.php';
require __DIR__.'/../framework/Session/Session.php';
require __DIR__.'/../app/models/Users.php';
require __DIR__.'/../app/models/Guestbook.php';
require __DIR__.'/../framework/Database/Dba.php';
require __DIR__.'/../framework/Database/Db.php';
require __DIR__.'/../framework/Config/Config.php';
require __DIR__.'/../framework/Routing/Controllers/Controller.php';
require __DIR__.'/../framework/Http/Response.php';
require __DIR__.'/../framework/Http/Request.php';
require __DIR__.'/../framework/Http/RedirectResponse.php';
require __DIR__.'/../framework/Http/JsonResponse.php';
require __DIR__.'/../framework/Http/ParameterBag.php';
require __DIR__.'/../framework/Http/ServerBag.php';
require __DIR__.'/../framework/Http/HeaderBag.php';
require __DIR__.'/../framework/Http/ResponseHeaderBag.php';
require __DIR__.'/../framework/Routing/Exception/ExceptionInterface.php';
require __DIR__.'/../framework/HttpCore/Exception/HttpExceptionInterface.php';
require __DIR__.'/../framework/HttpCore/Exception/HttpException.php';
require __DIR__.'/../framework/Routing/Exception/MethodNotAllowedException.php';
require __DIR__.'/../framework/Routing/Exception/ResourceNotFoundException.php';
require __DIR__.'/../framework/HttpCore/Exception/MethodNotAllowedHttpException.php';
require __DIR__.'/../framework/HttpCore/Exception/NotFoundHttpException.php';
require __DIR__.'/../framework/Support/AliasLoader.php';
require __DIR__.'/../framework/Support/Helpers.php';
require __DIR__.'/../framework/HttpCore/Exception/ExceptionHandler.php';


/**

  TODO:
  - Use Composer autoloader/any autoloader to properly resolve instantiating classes
  - Second todo item

**/

class_alias( 'Framework\Routing\Controllers\Controller','Controller',true);
require __DIR__.'/../app/controllers/GuestbookController.php';

define('BASE_URL', '/');
define('APP_PATH', realpath(__DIR__)."/../app");

use Framework\Support\AliasLoader;
use Framework\Config\Config;

$config = Config::getInstance();

AliasLoader::getInstance($config->config_values['aliases'])->register();


