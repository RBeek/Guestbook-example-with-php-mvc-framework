<?php namespace Framework\Routing\Exception;

/**
 * The resource was found but the request method is not allowed.
 *
 * This exception should trigger an HTTP 405 response in your application code.
 *
 */
class MethodNotAllowedException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @var array
     */
    protected $allowedMethods = array();

    public function __construct(array $allowedMethods, $message = null, $code = 0, Exception $previous = null)
    {
        $this->allowedMethods = array_map('strtoupper', $allowedMethods);

        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the allowed HTTP methods.
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }
}
