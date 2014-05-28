<?php
namespace Framework\Routing;

use Closure;
use Framework\Http\Response;
use Framework\Http\Request;
use Framework\Routing\Exception\ExceptionInterface;
use Framework\Routing\Exception\ResourceNotFoundException;
use Framework\Routing\Exception\MethodNotAllowedException;
use Framework\HttpCore\Exception\NotFoundHttpException;
use Framework\HttpCore\Exception\MethodNotAllowedHttpException;
use Framework\Support\AliasLoader;

/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - getRoutes()
 * - getCurrentRoute()
 * - setCurrentRoute()
 * - getRequest()
 * - get()
 * - post()
 * - any()
 * - getOptional()
 * - filter()
 * - getFilter()
 * - make()
 * - getAlias()
 * - build()
 * - getDependencies()
 * - resolveNonClass()
 * - getClassBasedFilter()
 * - createRoute()
 * - prepare()
 * - sanitizeRequirement()
 * - setAttributes()
 * - parseAction()
 * - getName()
 * - getCallback()
 * - createControllerCallback()
 * - (()
 * - dispatch()
 * - findRoute()
 * - match()
 * - matchCollection()
 * - getAttributes()
 * - handleRouteRequirements()
 * - handleRoutingException()
 * - mergeDefaults()
 * - compilePattern()
 * - findNextSeparator()
 * Classes list:
 * - BindingResolutionException extends \
 * - Router
 */

class BindingResolutionException extends \Exception
{
}
class Router
{
    const REQUIREMENT_MATCH = 0;
    const REQUIREMENT_MISMATCH = 1;
    const ROUTE_MATCH = 2;

    protected $routes;
    protected $currentRequest;
    protected $currentRoute;
    protected $context;
    protected $patterns = array();
    protected $allow = array();
    protected $filters = array();

    public function __construct()
    {
        $this->routes = new routecollection;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    public function setCurrentRoute(Route $route)
    {
        $this->currentRoute = $route;
    }

    public function getRequest()
    {
        return $this->currentRequest;
    }

    public function get($pattern, $action)
    {
        return $this->createRoute('get', $pattern, $action);
    }
    public function post($pattern, $action)
    {
        return $this->createRoute('post', $pattern, $action);
    }
    public function any($pattern, $action)
    {
        return $this->createRoute('get|post|put|patch|delete', $pattern, $action);
    }

    protected function getOptional($pattern)
    {
        $optional = array();

        preg_match_all('#\{(\w+)\?\}#', $pattern, $matches);

        // For each matching value, we will extract the name of the optional values
        // and add it to our array, then we will replace the place-holder to be
        // a valid place-holder minus this optional indicating question mark.
        foreach ($matches[0] as $key => $value) {
            $optional[] = $name = $matches[1][$key];

            $pattern = str_replace($value, '{' . $name . '}', $pattern);
        }

        return array($pattern, $optional);
    }

    /**
     * Register a new filter with the application.
     *
     * @param  string   $name
     * @param  Closure|string  $callback
     * @return void
     */
    public function filter($name, $callback)
    {
        $this->filters[$name] = $callback;
    }

    /**
     * Get a registered filter callback.
     *
     * @param  string   $name
     * @return Closure
     */
    public function getFilter($name)
    {
        if (array_key_exists($name, $this->filters)) {
            $filter = $this->filters[$name];

            // If the filter is a string, it means we are using a class based Filter which
            // allows for the easier testing of the filter's methods rather than trying
            // to test a Closure. So, we will resolve the class out of the container.
            if (is_string($filter)) {
                return $this->getClassBasedFilter($filter);
            }

            return $filter;
        }
    }

    protected function make($abstract)
    {

        /**
         TODO:
         - Testing
         - Second todo item
         *
         */

        $abstract = $this->getAlias($abstract);

        // no DI or other bindings under a different name to verify in this implementation
        // $concrete and $abstract will remain the same
        $concrete = $abstract;
        if (class_exists($abstract, true) or $concrete instanceof Closure) {
            $object = $this->build($concrete, $parameters);
        }

        return $object;
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @param  string  $abstract
     * @return string
     */
    protected function getAlias($abstract)
    {
        $aliases = AliasLoader::getInstance()->getAliases();
        return isset($aliases[$abstract]) ? $aliases[$abstract] : $abstract;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  string  $concrete
     * @param  array   $parameters
     * @return mixed
     */
    public function build($concrete, $parameters = array())
    {

        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new \ReflectionClass($concrete);

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface of Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (!$reflector->isInstantiable()) {
            $message = "Target [$concrete] is not instantiable.";

            throw new BindingResolutionException($message);
        }

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right aways.
        if (is_null($constructor)) {
            return new $concrete;
        }

        $parameters = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.



        /**
         TODO:
         - Dependancies testing
         - Second todo item
         *
         */

        $dependencies = $this->getDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getDependencies($parameters)
    {
        $dependencies = array();

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we'll just bomb out with an error since we have no-where to go.
            if (is_null($dependency)) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->make($dependency->name);
            }
        }

        return (array)$dependencies;
    }

    /**
     * Resolve a non-class hinted dependency.
     *
     * @param  ReflectionParameter  $parameter
     * @return mixed
     */
    protected function resolveNonClass(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        } else {
            $message = "Unresolvable dependency resolving [$parameter].";

            throw new BindingResolutionException($message);
        }
    }

    /**
     * Get a callable array for a class based filter.
     *
     * @param  string  $filter
     * @return array
     */
    protected function getClassBasedFilter($filter)
    {
        if (h::str_contains($filter, '@')) {
            list($class, $method) = explode('@', $filter);

            return array($this->make($class), $method);
        }

        return array($this->make($filter), 'filter');
    }

    public function createRoute($method, $pattern, $action)
    {

        if (!is_array($action)) {

            $action = $this->parseAction($action);
        }

        $route = new Route($pattern);

        // We will create the routes, setting the Closure callbacks on the instance
        // so we can easily access it later. If there are other parameters on a
        // route we'll also set those requirements as well - such as defaults.
        $route->setOptions(array('_call' => $this->getCallback($action)));
        $route->setRouter($this);
        $route->addRequirements($this->patterns);
        $route->setRequirement('_method', $method);

        list($pattern, $optional) = $this->getOptional($pattern);

        // Once we have created the route, we will add them to our route collection
        // which contains all the other routes and is used to match on incoming
        // URL and their appropriate route destination and on URL generation.
        $this->setAttributes($route, $action, $optional);

        $name = $this->getName($method, $pattern, $action);

        $this->routes->add($name, $route);

        return $route;
    }

    public function prepare($value, Request $request)
    {
        if (!$value instanceof Response) $value = new Response($value);

        return $value->prepare($request);
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

    /**
     * Set the attributes and requirements on the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $action
     * @param  array  $optional
     * @return void
     */
    protected function setAttributes(Route $route, $action, $optional)
    {

        // First we will set the requirement for the HTTP schemes. Some routes may
        // only respond to requests using the HTTPS scheme, while others might
        // respond to all, regardless of the scheme, so we'll set that here.
        if (in_array('https', $action)) {
            $route->setRequirement('_scheme', 'https');
        }

        if (in_array('http', $action)) {
            $route->setRequirement('_scheme', 'http');
        }

        // Once the scheme requirements have been made, we will set the before and
        // after middleware options, which will be used to run any middlewares
        // by the consuming library, making halting the request cycles easy.
        if (isset($action['before'])) {
            $route->setBeforeFilters($action['before']);
        }

        if (isset($action['after'])) {
            $route->setAfterFilters($action['after']);
        }

        // If there is a "uses" key on the route it means it is using a controller
        // instead of a Closures route. So, we'll need to set that as an option
        // on the route so we can easily do reverse routing ot the route URI.
        if (isset($action['uses'])) {
            $route->setOption('_uses', $action['uses']);
        }

        if (isset($action['domain'])) {
            $route->setHost($action['domain']);
        }

        // Finally we will go through and set all of the default variables to null
        // so the developer doesn't have to manually specify one each time they
        // are declared on a route. This is simply for developer convenience.
        foreach ($optional as $key) {
            $route->setDefault($key, null);
        }
    }

    protected function parseAction($action)
    {
        if ($action instanceof Closure) {
            return array($action);
        } elseif (is_string($action)) {
            return array('uses' => $action);
        }

        throw new \InvalidArgumentException("Unroutable action.");
    }

    protected function getName($method, $pattern, array $action)
    {
        if (isset($action['as'])) return $action['as'];

        $domain = isset($action['domain']) ? $action['domain'] . ' ' : '';

        return "{$domain}{$method} {$pattern}";
    }

    /**
     * Get the callback from the given action array.
     *
     * @param  array    $action
     * @return Closure
     */
    protected function getCallback(array $action)
    {
        foreach ($action as $key => $attribute) {

            // If the action has a "uses" key, the route is pointing to a controller
            // action instead of using a Closure. So, we'll create a Closure that
            // resolves the controller instances and calls the needed function.
            if ($key === 'uses') {
                return $this->createControllerCallback($attribute);
            } elseif ($attribute instanceof Closure) {
                return $attribute;
            }
        }
    }

    /**
     * Create the controller callback for a route.
     *
     * @param  string   $attribute
     * @return Closure
     */
    protected function createControllerCallback($attribute)
    {

        $me = $this;

        // We'll return a Closure that is able to resolve the controller instance and
        // call the appropriate method on the controller, passing in the arguments
        // it receives.
        return function () use ($me, $attribute)
        {
            list($controller, $method) = explode('@', $attribute);

            $route = $me->getCurrentRoute();

            // We will extract the passed in parameters off of the route object so we will
            // pass them off to the controller method as arguments. We will not get the
            // defaults so that the controllers will be able to use its own defaults.
            $args = array_values($route->getParametersWithoutDefaults());

            $instance = new $controller;

            return $instance->callAction($me, $method, $args);
        };
    }

    public function dispatch(Request $request)
    {
        $this->currentRequest = $this->context = $request;

        $response = null;
        if (!is_null($response)) {

            $response = $this->prepare($response, $request);
        }

        // Once we have the route, we can just run it to get the responses, which will
        // always be instances of the Response class.
        else {
            $this->currentRoute = $route = $this->findRoute($request);

            $response = $route->run($request);
        }

        return $response;
    }

    /**
     * Match the given request to a route object.
     *
     * @param  \Request  $request
     * @return \Routing\Route
     */
    protected function findRoute(Request $request)
    {
        try {
            $path = $request->getPathInfo();

            $parameters = $this->match($path);
        }

        // Routing will type-hint it to make sure we're only providing special handling
        // for those exceptions, and not other random exceptions that occur.
        catch(ExceptionInterface $e) {

            $this->handleRoutingException($e);
        }

        $route = $this->routes->get($parameters['_route']);

        // If we found a route, we will grab the actual route objects out of this
        // route collection and set the matching parameters on the instance so
        // we will easily access them later if the route action is executed.
        $route->setParameters($parameters);

        return $route;
    }

    public function match($pathinfo)
    {
        $this->allow = array();

        if ($ret = $this->matchCollection(rawurldecode($pathinfo), $this->routes)) {

            // if (0 === strpos($ret['_route'],"post")) {
            //   var_dump($ret);
            //   exit();
            // }
            return $ret;
        }

        throw 0 < count($this->allow) ? new MethodNotAllowedException(array_unique(array_map('strtoupper', $this->allow))) : new ResourceNotFoundException();
    }

    /**
     * Tries to match a URL with a set of routes.
     *
     * @param string          $pathinfo The path info to be parsed
     * @param RouteCollection $routes   The set of routes
     *
     * @return array An array of parameters
     *
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    protected function matchCollection($pathinfo, RouteCollection $routes)
    {
        foreach ($routes as $name => $route) {
            $compiledRoute = $route->compile();

            // check the static prefix of the URL first. Only use the more expensive preg_match when it matches
            if ('' !== $compiledRoute->getStaticPrefix() && 0 !== strpos($pathinfo, $compiledRoute->getStaticPrefix())) {
                continue;
            }

            if (!preg_match($compiledRoute->getRegex(), $pathinfo, $matches)) {
                continue;
            }

            $hostMatches = array();
            if ($compiledRoute->getHostRegex() && !preg_match($compiledRoute->getHostRegex(), $this->context->getHost(), $hostMatches)) {
                continue;
            }

            // check HTTP method requirement
            if ($req = $route->getRequirement('_method')) {

                // HEAD and GET are equivalent as per RFC
                if ('HEAD' === $method = $this->context->getMethod()) {
                    $method = 'GET';
                }

                if (!in_array($method, $req = explode('|', strtoupper($req)))) {
                    $this->allow = array_merge($this->allow, $req);

                    continue;
                }
            }

            $status = $this->handleRouteRequirements($pathinfo, $name, $route);

            if (self::ROUTE_MATCH === $status[0]) {
                return $status[1];
            }

            if (self::REQUIREMENT_MISMATCH === $status[0]) {
                continue;
            }

            return $this->getAttributes($route, $name, array_replace($matches, $hostMatches));
        }
    }

    protected function getAttributes(Route $route, $name, array $attributes)
    {
        $attributes['_route'] = $name;

        return $this->mergeDefaults($attributes, $route->getDefaults());
    }

    /**
     * Handles specific route requirements.
     *
     * @param string $pathinfo The path
     * @param string $name     The route name
     * @param Route  $route    The route
     *
     * @return array The first element represents the status, the second contains additional information
     */
    protected function handleRouteRequirements($pathinfo, $name, Route $route)
    {

        // check HTTP scheme requirement
        $scheme = $route->getRequirement('_scheme');
        $status = $scheme && $scheme !== $this->context->getScheme() ? self::REQUIREMENT_MISMATCH : self::REQUIREMENT_MATCH;

        return array($status, null);
    }

    /**
     * Convert routing exception to Http version.
     *
     * @param  Exception  $e
     * @return void
     */
    protected function handleRoutingException(\Exception $e)
    {
        if ($e instanceof ResourceNotFoundException) {
            throw new NotFoundHttpException($e->getMessage());
        }

        // The method not allowed exception is essentially a HTTP 405 error, so we
        // will grab the allowed methods when converting into the HTTP Kernel's
        // version of the exact error. This gives us a good RESTful API site.
        elseif ($e instanceof MethodNotAllowedException) {
            $allowed = $e->getAllowedMethods();

            throw new MethodNotAllowedHttpException($allowed, $e->getMessage());
        }
    }

    /**
     * Get merged default parameters.
     *
     * @param array $params   The parameters
     * @param array $defaults The defaults
     *
     * @return array Merged default parameters
     */
    protected function mergeDefaults($params, $defaults)
    {
        foreach ($params as $key => $value) {
            if (!is_int($key)) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    public function compilePattern($pattern = "", $isHost = false)
    {
        $pattern = $this->getPath();
        $route = $this;
        $tokens = array();
        $variables = array();
        $matches = array();
        $pos = 0;
        $defaultSeparator = $isHost ? '.' : '/';

        // Match all variables enclosed in "{}" and iterate over them. But we only want to match the innermost variable
        // in case of nested "{}", e.g. {foo{bar}}. This in ensured because \w does not match "{" or "}" itself.
        preg_match_all('#\{\w+\}#', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($matches as $match) {
            $varName = substr($match[0][0], 1, -1);

            // get all static text preceding the current variable
            $precedingText = substr($pattern, $pos, $match[0][1] - $pos);
            $pos = $match[0][1] + strlen($match[0][0]);
            $precedingChar = strlen($precedingText) > 0 ? substr($precedingText, -1) : '';
            $isSeparator = '' !== $precedingChar && false !== strpos(static ::SEPARATORS, $precedingChar);

            if (is_numeric($varName)) {
                throw new \DomainException(sprintf('Variable name "%s" cannot be numeric in route pattern "%s". Please use a different name.', $varName, $pattern));
            }
            if (in_array($varName, $variables)) {
                throw new \LogicException(sprintf('Route pattern "%s" cannot reference variable name "%s" more than once.', $pattern, $varName));
            }

            if ($isSeparator && strlen($precedingText) > 1) {
                $tokens[] = array('text', substr($precedingText, 0, -1));
            } elseif (!$isSeparator && strlen($precedingText) > 0) {
                $tokens[] = array('text', $precedingText);
            }

            $regexp = $route->getRequirement($varName);
            if (null === $regexp) {
                $followingPattern = (string)substr($pattern, $pos);

                // Find the next static character after the variable that functions as a separator. By default, this separator and '/'
                // are disallowed for the variable. This default requirement makes sure that optional variables can be matched at all
                // and that the generating-matching-combination of URLs unambiguous, i.e. the params used for generating the URL are
                // the same that will be matched. Example: new Route('/{page}.{_format}', array('_format' => 'html'))
                // If {page} would also match the separating dot, {_format} would never match as {page} will eagerly consume everything.
                // Also even if {_format} was not optional the requirement prevents that {page} matches something that was originally
                // part of {_format} when generating the URL, e.g. _format = 'mobile.html'.
                $nextSeparator = self::findNextSeparator($followingPattern);
                $regexp = sprintf('[^%s%s]+', preg_quote($defaultSeparator, self::REGEX_DELIMITER), $defaultSeparator !== $nextSeparator && '' !== $nextSeparator ? preg_quote($nextSeparator, self::REGEX_DELIMITER) : '');
                if (('' !== $nextSeparator && !preg_match('#^\{\w+\}#', $followingPattern)) || '' === $followingPattern) {

                    // When we have a separator, which is disallowed for the variable, we can optimize the regex with a possessive
                    // quantifier. This prevents useless backtracking of PCRE and improves performance by 20% for matching those patterns.
                    // Given the above example, there is no point in backtracking into {page} (that forbids the dot) when a dot must follow
                    // after it. This optimization cannot be applied when the next char is no real separator or when the next variable is
                    // directly adjacent, e.g. '/{x}{y}'.
                    $regexp.= '+';
                }
            }

            $tokens[] = array('variable', $isSeparator ? $precedingChar : '', $regexp, $varName);
            $variables[] = $varName;
        }

        if ($pos < strlen($pattern)) {
            $tokens[] = array('text', substr($pattern, $pos));
        }

        // find the first optional token
        $firstOptional = PHP_INT_MAX;
        if (!$isHost) {
            for ($i = count($tokens) - 1; $i >= 0; $i--) {
                $token = $tokens[$i];
                if ('variable' === $token[0] && $route->hasDefault($token[3])) {
                    $firstOptional = $i;
                } else {
                    break;
                }
            }
        }

        // compute the matching regexp
        $regexp = '';

        for ($i = 0, $nbToken = count($tokens); $i < $nbToken; $i++) {
            $regexp.= self::computeRegexp($tokens, $i, $firstOptional);
        }

        return array('staticPrefix' => 'text' === $tokens[0][0] ? $tokens[0][1] : '', 'regex' => self::REGEX_DELIMITER . '^' . $regexp . '$' . self::REGEX_DELIMITER . 's', 'tokens' => array_reverse($tokens), 'variables' => $variables,);
    }

    private static function findNextSeparator($pattern)
    {
        if ('' == $pattern) {

            // return empty string if pattern is empty or false (false which can be returned by substr)
            return '';
        }

        // first remove all placeholders from the pattern so we can find the next real static character
        $pattern = preg_replace('#\{\w+\}#', '', $pattern);

        return isset($pattern[0]) && false !== strpos(static ::SEPARATORS, $pattern[0]) ? $pattern[0] : '';
    }
}
