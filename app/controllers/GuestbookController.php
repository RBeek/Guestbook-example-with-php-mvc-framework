<?php

class GuestbookController extends Controller
{

private $model = null;
private $error;

public function __construct($dba = null)
{
    $this->model = new Guestbook;
}


public function index(Request $request )
{

  $view = "index";
  $cache_id = md5( $view );
  $gview = new View;
  $tpl = new View;
  $tpl->setTemplateDir(__DIR__.'/../views');
  $gview->content = $tpl->fetch( $view.".php", $cache_id);
  $content = $gview->fetch( __DIR__.'/../layout/base.phtml');

return $content;

}

public function show()
{
  $guestbook = $this->model->all();


  $view = "show";
  $cache_id = md5( $view );
  $gview = new View;
  $tpl = new View;
  $tpl->guestbook = $guestbook;
  $tpl->setTemplateDir(__DIR__.'/../views');
  $gview->content = $tpl->fetch( $view.".php", $cache_id);
  $content = $gview->fetch( __DIR__.'/../layout/base.phtml');

return $content;

}


}