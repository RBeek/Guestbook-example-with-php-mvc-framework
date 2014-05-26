<?php
/**
* Class and Function List:
* Function list:
* - add()
* - count()
* - get()
* - getIterator()
* Classes list:
* - RouteCollection
*/
namespace Framework\Routing;

class RouteCollection implements \IteratorAggregate, \Countable
{
    private $routes = array();
    public function add($name, $route)
    {
        unset($this->routes[$name]);
        $this->routes[$name] = $route;
    }
    public function count()
    {
        return count($this->routes);
    }
    public function get($name)
    {
        return isset($this->routes[$name]) ? $this->routes[$name] : null;
    }
    public function getIterator()
    {
        return new \ArrayIterator($this->routes);
    }
}
