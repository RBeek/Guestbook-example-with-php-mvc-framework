<?php namespace Framework\Http;

class ParameterBag implements \IteratorAggregate
{
    protected $params;

    public function __construct(array $params = array()) {
        $this->params = $params;
    }
    public function all()
    {
        return $this->params;
    }
    
    public function get($path, $default = null, $deep = false){
        if (!$deep) {
            $ret = array_key_exists($path, $this->params) ? $this->params[$path] : $default;
        }
        return $ret;
    }
    public function set($key, $value) {
        $this->params[$key] = $value;
    }
    
    public function getIterator() {
        return new \ArrayIterator($this->params);
    }
    public function has($key)
    {
        return array_key_exists($key, $this->params);
    }

    public function remove($key)
    {
        unset($this->params[$key]);
    }
}