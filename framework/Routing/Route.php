<?php namespace Framework\Routing;

use Framework\Http\Response;
use Framework\Http\Request;
use Framework\Support\Helpers as h;

/**
* Class and Function List:
* Function list:
* - __construct()
* - run()
* - callCallable()
* - setParameters()
* - getHost()
* - setHost()
* - compile()
* - serialize()
* - unserialize()
* - setPath()
* - getPath()
* - getOption()
* - setOption()
* - setOptions()
* - addOptions()
* - setRouter()
* - getRequirement()
* - getParameters()
* - resolveParameter()
* - getParametersWithoutDefaults()
* - addRequirements()
* - isMissingDefault()
* - isOptional()
* - getDefaults()
* - hasDefault()
* - setDefault()
* - setRequirement()
* - sanitizeRequirement()
* - setMethods()
* Classes list:
* - Route
*/
class Route implements \Serializable
{

    protected $router;
    protected $methods = array();
    protected $path = '/';
    protected $defaults = array();
    protected $requirements = array();
    protected $options = array();
    protected $schemes = array();
    protected $parameters;
    protected $parsedParameters;
    protected $compiled;
    protected $host;

    function __construct($path, $defaults = array(), $requirements = array(), $methods = array(), $schemes = array(), $options = array(), $host = '')
    {
        $this->setPath($path);
        $this->methods = $methods;
        $this->requirements = $requirements;
        $this->defaults = $defaults;
        $this->options = $options;
        $this->schemes = $schemes;
        $this->setHost($host);
    }

    public function run(Request $request)
    {
        $this->parsedParameters = null;

        // We will only call the router callable if no "before" middlewares returned
        // a response. If they do, we will consider that the response to requests
        // so that the request "lifecycle" will be easily halted for filtering.
        $response = $this->callBeforeFilters($request);

        if (!isset($response)) {
            $response = $this->callCallable();
        }

        $response = $this->router->prepare($response, $request);

        return $response;
    }

    /**
     * Call all of the before filters on the route.
     *
     * @param  \Http\Request   $request
     * @return mixed
     */
    protected function callBeforeFilters(Request $request)
    {
        $before = $this->getAllBeforeFilters($request);

        $response = null;

        // Once we have each middlewares, we will simply iterate through them and call
        // each one of them with the request. We will set the response variable to
        // whatever it may return so that it may override the request processes.
        foreach ($before as $filter)
        {
            $response = $this->callFilter($filter, $request);

            if ( ! is_null($response)) return $response;
        }
    }

    /**
     * Get all of the before filters to run on the route.
     *
     * @param  \Http\Request   $request
     * @return array
     */
    protected function getAllBeforeFilters(Request $request)
    {
       return $before = $this->getBeforeFilters();
        // return array_merge($before, $this->router->findPatternFilters($request));
    }

    /**
     * Get the before filters on the route.
     *
     * @return array
     */
    public function getBeforeFilters()
    {
        return $this->getOption('_before') ?: array();
    }

    /**
     * Set the before filters on the route.
     *
     * @param  string  $value
     * @return void
     */
    public function setBeforeFilters($value)
    {
        $filters = is_string($value) ? explode('|', $value) : (array) $value;

        $this->setOption('_before', array_merge($this->getBeforeFilters(), $filters));
    }
    
    /**
     * Call a given filter with the parameters.
     *
     * @param  string  $name
     * @param  \Http\Request   $request
     * @param  array $params
     * @return mixed
     */
    public function callFilter($name, Request $request, array $params = array())
    {

        $merge = array($this->router->getCurrentRoute(), $request);

        $params = array_merge($merge, $params);

        // Next we will parse the filter name to extract out any parameters and adding
        // any parameters specified in a filter name to the end of the lists of our
        // parameters, since the ones at the beginning are typically very static.
        list($name, $params) = $this->parseFilter($name, $params);

        if ( ! is_null($callable = $this->router->getFilter($name)))
        {
            return call_user_func_array($callable, $params);
        }
    }

    /**
     * Parse a filter name and add any parameters to the array.
     *
     * @param  string  $name
     * @param  array   $parameters
     * @return array
     */
    protected function parseFilter($name, $parameters = array())
    {
        if (h::str_contains($name, ':'))
        {
            // If the filter name contains a colon, we will assume that the developer
            // is passing along some parameters with the name, and we will explode
            // out the name and paramters, merging the parameters onto the list.
            $segments = explode(':', $name);

            $name = $segments[0];

            // We will merge the arguments specified in the filter name into the list
            // of existing parameters. We'll send them at the end since any values
            // at the front are usually static such as request, response, route.
            $arguments = explode(',', $segments[1]);

            $parameters = array_merge($parameters, $arguments);
        }

        return array($name, $parameters);
    }

    /**
     * Call the callable Closure attached to the route.
     *
     * @return mixed
     */
    protected function callCallable()
    {
        $variables = array_values($this->getParametersWithoutDefaults());
        return call_user_func_array($this->getOption('_call'), $variables);
    }

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    public function getHost()
    {
        return $this->host;
    }
    public function setHost($pattern)
    {
        $this->host = (string)$pattern;
        $this->compiled = null;

        return $this;
    }

    public function compile()
    {
        if (null !== $this->compiled) {
            return $this->compiled;
        }

        return $this->compiled = RouteCompiler::compile($this);
    }

    public function serialize()
    {
        return serialize(array('path' => $this->path, 'methods' => $this->methods, 'defaults' => $this->defaults, 'requirements' => $this->requirements, 'options' => $this->options, 'schemes' => $this->schemes, 'host' => $this->host,));
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->path = $data['path'];
        $this->methods = $data['methods'];
        $this->defaults = $data['defaults'];
        $this->requirements = $data['requirements'];
        $this->options = $data['options'];
        $this->schemes = $data['schemes'];
        $this->host = $data['host'];
    }

    public function setPath($pattern)
    {

        // A pattern must start with a slash and must not have multiple slashes at the beginning because the
        // generated path for this route would be confused with a network path, e.g. '//domain.com/path'.
        $this->path = '/' . ltrim(trim($pattern), '/');
        $this->compiled = null;

        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        $this->compiled = null;

        return $this;
    }

    public function setOptions(array $options)
    {
        return $this->addOptions($options);
    }

    public function addOptions(array $options)
    {
        foreach ($options as $name => $option) {
            $this->options[$name] = $option;
        }
        return $this;
    }

    public function setRouter($router)
    {
        $this->router = $router;
        return $this;
    }

    public function getRequirement($key)
    {
        return isset($this->requirements[$key]) ? $this->requirements[$key] : null;
    }

    /**
     * Get the parameters to the callback.
     *
     * @return array
     */
    public function getParameters()
    {

        // If we have already parsed the parameters, we will just return the listing
        // that we already parsed as some of these may have been resolved through
        // a binder that uses a database repository and shouldn't be run again.
        if (isset($this->parsedParameters)) {
            return $this->parsedParameters;
        }

        $variables = $this->compile()->getVariables();

        // To get the parameter array, we need to spin the names of the variables on
        // the compiled route and match them to the parameters that we got when a
        // route is matched by the router, as routes instances don't have them.
        $parameters = array();

        foreach ($variables as $variable) {
            $parameters[$variable] = $this->resolveParameter($variable);
        }

        return $this->parsedParameters = $parameters;
    }

    /**
     * Resolve a parameter value for the route.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function resolveParameter($key)
    {
        $value = $this->parameters[$key];

        // If the parameter has a binder, we will call the binder to resolve the real
        // value for the parameters. The binders could make a database call to get
        // a User object for example or may transform the input in some fashion.
        // if ($this->router->hasBinder($key))
        // {
        //   return $this->router->performBinding($key, $value, $this);
        // }

        return $value;
    }

    /**
     * Get the route parameters without missing defaults.
     *
     * @return array
     */
    public function getParametersWithoutDefaults()
    {
        $parameters = $this->getParameters();

        foreach ($parameters as $key => $value) {

            // When calling functions using call_user_func_array, we don't want to write
            // over any existing default parameters, so we will remove every optional
            // parameter from the list that did not get a specified value on route.
            if ($this->isMissingDefault($key, $value)) {
                unset($parameters[$key]);
            }
        }

        return $parameters;
    }
    public function addRequirements(array $requirements)
    {
        foreach ($requirements as $key => $regex) {
            $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
            $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
        }
        $this->compiled = null;

        return $this;
    }

    protected function isMissingDefault($key, $value)
    {
        return $this->isOptional($key) and is_null($value);
    }
    public function isOptional($key)
    {
        return array_key_exists($key, $this->getDefaults());
    }
    public function getDefaults()
    {
        return $this->defaults;
    }
    public function hasDefault($name)
    {
        return array_key_exists($name, $this->defaults);
    }

    public function setDefault($name, $default)
    {
        $this->defaults[$name] = $default;
        $this->compiled = null;

        return $this;
    }

    public function setRequirement($key, $regex)
    {
        $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
        $this->compiled = null;

        return $this;
    }

    private function sanitizeRequirement($key, $regex)
    {
        if (!is_string($regex)) {
            throw new \InvalidArgumentException(sprintf('Routing requirement for "%s" must be a string.', $key));
        }

        if ('' !== $regex && '^' === $regex[0]) {
            $regex = (string)substr($regex, 1);

            // returns false for a single character


        }

        if ('$' === substr($regex, -1)) {
            $regex = substr($regex, 0, -1);
        }

        if ('' === $regex) {
            throw new \InvalidArgumentException(sprintf('Routing requirement for "%s" cannot be empty.', $key));
        }

        // this is to keep BC and will be removed in a future version
        if ('_scheme' === $key) {
            $this->setSchemes(explode('|', $regex));
        } elseif ('_method' === $key) {
            $this->setMethods(explode('|', $regex));
        }

        return $regex;
    }
    public function setMethods($methods)
    {
        $this->methods = array_map('strtoupper', (array)$methods);

        // this is to keep BC and will be removed in a future version
        if ($this->methods) {
            $this->requirements['_method'] = implode('|', $this->methods);
        } else {
            unset($this->requirements['_method']);
        }

        $this->compiled = null;

        return $this;
    }
}
