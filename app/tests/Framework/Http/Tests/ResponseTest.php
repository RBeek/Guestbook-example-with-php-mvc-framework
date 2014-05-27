<?php
/**
* Class and Function List:
* Function list:
* - testCreate()
* - testPrepareSetContentType()
* - createDateTimeOneHourAgo()
* - createDateTimeOneHourLater()
* - createDateTimeNow()
* - provideResponse()
* - __toString()
* Classes list:
* - ResponseTest extends ResponseTestCase
* - StringableObject
*/
namespace Framework\Http\Tests;

use Framework\Http\Response;
use Framework\Http\Request;

class ResponseTest extends ResponseTestCase
{
    public function testCreate()
    {
        $response = Response::create('foo', 301, array('Foo' => 'bar'));

        $this->assertInstanceOf('Framework\Http\Response', $response);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('bar', $response->headers->get('foo'));
    }

    public function testPrepareSetContentType()
    {
        $response = new Response('foo');
        $request = Request::create('/');
        $request->setRequestFormat('json');

        $response->prepare($request);

        $this->assertEquals('application/json', $response->headers->get('content-type'));
    }

    protected function createDateTimeOneHourAgo()
    {
        $date = new \DateTime();

        return $date->sub(new \DateInterval('PT1H'));
    }

    protected function createDateTimeOneHourLater()
    {
        $date = new \DateTime();

        return $date->add(new \DateInterval('PT1H'));
    }

    protected function createDateTimeNow()
    {
        return new \DateTime();
    }

    protected function provideResponse()
    {
        return new Response();
    }
}

class StringableObject
{
    public function __toString()
    {
        return 'Foo';
    }
}
