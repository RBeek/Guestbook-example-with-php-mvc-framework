<?php namespace Framework\Routing\Controllers;

use Closure;
use ReflectionClass;
use Framework\Routing\Router;
use Framework\HttpCore\Exception\NotFoundHttpException;

class Controller {

protected $filters = array();
protected $callbackFilters = array();
protected $layout;

// /**
//  * Call the before filters on the controller.
//  *
//  * @param  \Illuminate\Routing\Router  $router
//  * @param  string  $method
//  * @return mixed
//  */
// protected function callBeforeFilters($router, $method)
// {
//     $response = null;

//     $route = $router->getCurrentRoute();

//     // When running the before filters we will simply spin through the list of the
//     // filters and call each one on the current route objects, which will place
//     // the proper parameters on the filter call, including the requests data.
//     $request = $router->getRequest();

//     $filters = $this->getBeforeFilters($request, $method);

//     foreach ($filters as $filter)
//     {
//         $response = $this->callFilter($route, $filter, $request);

//         if ( ! is_null($response)) return $response;
//     }
// }


//     /**
//      * Call the given route filter.
//      *
//      * @param  \Illuminate\Routing\Route  $route
//      * @param  string  $filter
//      * @param  \Symfony\Component\HttpFoundation\Request  $request
//      * @param  array  $parameters
//      * @return mixed
//      */
//     protected function callFilter($route, $filter, $request, $parameters = array())
//     {
//         if (isset($this->callbackFilters[$filter]))
//         {
//             $callback = $this->callbackFilters[$filter];

//             return call_user_func_array($callback, array_merge(array($route, $request), $parameters));
//         }

//         return $route->callFilter($filter, $request, $parameters);
//     }
private function getArguments($method){
    $r = new ReflectionClass(get_called_class());
    return $r->getMethod($method)->getParameters();
}

/**
* Execute an action on the controller.
*
* @param  \Router  $router
* @param  string  $method
* @param  array   $parameters
* @return \Response
*/
public function callAction( Router $router, $method, $parameters)
{
    // $response = $this->callBeforeFilters($router, $method);
    // $this->setupLayout();
    
    if ($params = $this->getArguments($method)) {
        if (count($params === 1)) {
           if( "Framework\Http\Request" == $class =  @$params[0]->getClass()->name){
            // echo "boe";exit();
            $parameters = array(0 => $router->getRequest());
            }
        }
     }

    $response=null;
    if (is_null($response))
    {
    $response = $this->callMethod($method, $parameters);
    }
    // if (is_null($response) and ! is_null($this->layout))
    // {
    //   $response = $this->layout;
    // }
    return $this->processResponse($router, $method, $response);
}
/**
* Call the given action with the given parameters.
*
* @param  string  $method
* @param  array   $parameters
* @return mixed
*/
protected function callMethod($method, $parameters)
{
    return call_user_func_array(array($this, $method), $parameters);
}
/**
* Process a controller action response.
*
* @param  \Illuminate\Routing\Router  $router
* @param  string  $method
* @param  mixed   $response
* @return \Symfony\Component\HttpFoundation\Response
*/
protected function processResponse($router, $method, $response)
{
    $request = $router->getRequest();
    $response = $router->prepare($response, $request);
    return $response;
}
/**
* Handle calls to missing methods on the controller.
*
* @param  array   $parameters
* @return mixed
*/
public function missingMethod($parameters)
{
    throw new Exception("NotFoundHttp");
}
/**
* Handle calls to missing methods on the controller.
*
* @param  string  $method
* @param  array   $parameters
* @return mixed
*/
public function __call($method, $parameters)
{
    throw new Exception("NotFoundHttpException");
}
}