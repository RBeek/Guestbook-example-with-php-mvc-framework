<?php namespace Framework\Session\Tests;

use Framework\Session\Session;
// use Framework\Http\Request;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    protected $session;
    protected $storage;

    protected function setUp()
    {
        $this->storage = new MockArraySessionStorage();
        $this->session = new Session($this->storage);
    }

    protected function tearDown()
    {
        $this->storage = null;
        $this->session = null;
    }

    public function testIsStarted()
    {
        $this->assertFalse($this->session->isStarted());
        $this->session->start();
        $this->assertTrue($this->session->isStarted());
    }

    public function testStart()
    {
        $this->assertEquals('', $this->session->getId());
        $this->assertTrue($this->session->start());
        $this->assertNotEquals('', $this->session->getId());
    }


    public function testGet()
    {
        // tests defaults
        $this->assertNull($this->session->get('foo'));
        $this->assertEquals(1, $this->session->get('foo', 1));
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($key, $value)
    {

        $this->session->set($key, $value);
        $this->assertEquals($value, $this->session->get($key));
    }

    /**
     * @dataProvider setProvider
     */
    public function testHas($key, $value)
    {
        $this->session->set($key, $value);
        $this->assertTrue($this->session->has($key));
        $this->assertFalse($this->session->has($key.'non_value'));
    }


    /**
     * @dataProvider setProvider
     */
    public function testAll($key, $value, $result)
    {
        $this->session->set($key, $value);
        $this->assertEquals($result, $this->session->all());
    }

    // /**
    //  * @dataProvider setProvider
    //  */
    // public function testClear($key, $value)
    // {
    //     $this->session->set('hi', 'fabien');
    //     $this->session->set($key, $value);
    //     $this->session->clear();
    //     $this->assertEquals(array(), $this->session->all());
    // }

    public function setProvider()
    {
        return array(
            array('foo', 'bar', array('foo' => 'bar')),
            array('foo.bar', 'too much beer', array('foo.bar' => 'too much beer')),
            array('great', 'symfony2 is great', array('great' => 'symfony2 is great')),
        );
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
