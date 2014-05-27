<?php namespace Framework\Http\Tests;

// use \Mockery as m;
use Framework\Http\Request;
// use Framework\Http\Response

class RequestTest extends \PHPUnit_Framework_TestCase
{

    public $test;
    public $request;
    public function setUp()
    {
        if (!class_exists('Framework\Http\Request')) {
            $this->markTestSkipped('The "Http" component is not available');
        }
        // $this->request = Request::createFromGlobals();
        // $this->test = $this->getMock('Boo\Http\Request');
    }

    public function tearDown()
    {
        // m::close();
    }



    /**
     * @covers Framework\Http\Request::__construct
     */
    public function testConstructor()
    {
        $this->testInit();
    }

    /**
     * @covers Framework\Http\Request::init
     */
    public function testInit()
    {
        $request = new Request();

        $request->init(array('foo' => 'bar'));
        $this->assertEquals('bar', $request->query->get('foo'), '->init() takes an array of query parameters as its first argument');

        $request->init(array(), array('foo' => 'bar'));
        $this->assertEquals('bar', $request->request->get('foo'), '->init() takes an array of request parameters as its second argument');

        $request->init(array(), array(), array('foo' => 'bar'));
        $this->assertEquals('bar', $request->attributes->get('foo'), '->init() takes an array of attributes as its third argument');

        $request->init(array(), array(), array(), array(), array(), array('HTTP_FOO' => 'bar'));
        $this->assertEquals('bar', $request->headers->get('FOO'), '->init() takes an array of HTTP headers as its fourth argument');
    }

    /**
     * @covers Framework\Http\Request::create
     */
    public function testCreate()
    {
        $request = Request::create('http://test.com/foo?bar=baz');
        $this->assertEquals('http://test.com/foo?bar=baz', $request->getUri());
        $this->assertEquals('/foo', $request->getPathInfo());
        $this->assertEquals('bar=baz', $request->getQueryString());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('test.com', $request->getHttpHost());
        $this->assertFalse($request->isSecure());

        $request = Request::create('http://test.com/foo', 'GET', array('bar' => 'baz'));
        $this->assertEquals('http://test.com/foo?bar=baz', $request->getUri());
        $this->assertEquals('/foo', $request->getPathInfo());
        $this->assertEquals('bar=baz', $request->getQueryString());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('test.com', $request->getHttpHost());
        $this->assertFalse($request->isSecure());

    }
    // public function testMock() {
        // $this->test = new Request;
            // $this->test->expects($this->any())
            //     ->method('test')
            //     ->will($this->returnValue(null));

            // $this->assertEquals("boehoe", $this->request->test());
        // }
}