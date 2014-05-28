<?php
require __DIR__.'/../bootstrap/autoload.php';
require_once __DIR__ . '/../bootstrap/start.php';

/**
* Class and Function List:
* Function list:
* - decorate()
* - getContent()
* - (()
* - (()
* Classes list:
*/


function decorate($content, $css = '')
{
    return <<<EOF
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <meta name="robots" content="noindex,nofollow" />
        <style>
            /* Copyright (c) 2010, Yahoo! Inc. All rights reserved. Code licensed under the BSD License: http://developer.yahoo.com/yui/license.html */
            html{color:#000;background:#FFF;}body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,input,textarea,p,blockquote,th,td{margin:0;padding:0;}table{border-collapse:collapse;border-spacing:0;}fieldset,img{border:0;}address,caption,cite,code,dfn,em,strong,th,var{font-style:normal;font-weight:normal;}li{list-style:none;}caption,th{text-align:left;}h1,h2,h3,h4,h5,h6{font-size:100%;font-weight:normal;}q:before,q:after{content:'';}abbr,acronym{border:0;font-variant:normal;}sup{vertical-align:text-top;}sub{vertical-align:text-bottom;}input,textarea,select{font-family:inherit;font-size:inherit;font-weight:inherit;}input,textarea,select{*font-size:100%;}legend{color:#000;}

            html { background: #eee; padding: 10px }
            img { border: 0; }
            #sf-resetcontent { width:970px; margin:0 auto; }
            $css
        </style>
    </head>
    <body>
        $content
    </body>
</html>
EOF;

}

function getContent($exception)
{
    switch ($exception->getStatusCode()) {
        case 404:
            $title = 'Sorry, the page you are looking for could not be found.';
            break;

        default:
            $title = 'Whoops, looks like something went wrong.';
    }

    $content = "";
    return <<<EOF
              <div id="sf-resetcontent" class="sf-reset">
                  <h1>$title</h1>
                  $content
              </div>
EOF;

}

try {

    $request = Request::createFromGlobals();
    $request->setSession(new Session);

    $r = new Router();
    $route0 = $r->get("/", array("uses" => "GuestbookController@index", "before" => "doSomething"));
    $route1 = $r->get("/guestbook", "GuestbookController@show");
    $route2 = $r->post("/", function () use ($request)
    {
        $name = $request->request->get("name", false);
        $email = $request->request->get("email", false);
        $guestbook = $request->request->get("guestbook", false);
        $guestbook = $request->request->all();
        if (isset($guestbook['addguestbook'])) unset($guestbook['addguestbook']);

        $model = new Guestbook;
        $added = $model->add($guestbook);

        return new Redirect(BASE_URL);

        // $now = new DateTime();
        // $sqltime = $now->format('Y-m-d H:i:s');
        // $this->generateUrl('homepage')

    });

    $route3 = $r->get("/api/guestbook", function () use ($request)
    {
        $guestbook = new Guestbook;
        $guestbook = $guestbook->all();

        return new JsnResponse($guestbook);
    });

    $response = $r->dispatch($request);
    $response->send();
}
catch(Exception $e) {
    if( $e instanceof \Framework\HttpCore\Exception\HttpExceptionInterface){
        echo decorate(getContent($exception));
    }else{

        // echo $e->getMessage();
    }

}
