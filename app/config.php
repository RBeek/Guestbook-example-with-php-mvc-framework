<?php
return array(

'connections' => array(

  'sqlite' => array(
    'driver'   => 'sqlite',
    'database' => __DIR__.'/../database/production.sqlite',
    'prefix'   => '',
  ),

  'mysql' => array(
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'test',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
    'port'      => '3306',

  )),


'aliases' => array(
  'Dba'        => 'Framework\Database\Dba',
  'Db'         => 'Framework\Database\Db',
  'View'       => 'Framework\View\View',
  'Request'    => 'Framework\Http\Request',
  'Router'     => 'Framework\Routing\Router',
  'Redirect'   => 'Framework\Http\RedirectResponse',
  'JsnResponse'=> 'Framework\Http\JsonResponse',
  'Config'     => 'Framework\Config\Config',
  'Session'    => 'Framework\Session\Session',
  'Controller' => 'Framework\Routing\Controllers\Controller',

));
