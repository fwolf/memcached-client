<?php
namespace Fwlib\MemcachedClient\Test;


/**
 * For running test, you need a memcached server, set host and port in
 * property $memcachedServerHost and $memcachedServerPort.
 *
 * @copyright   Copyright 2014 Fwolf
 * @license     http://opensource.org/licenses/mit-license MIT
 */
class MemcachedClientTest extends \PHPUnit_Framework_TestCase
{
    private $memcachedServerHost = '127.0.0.1';
    private $memcachedServerPort = 11211;
    private $keyPrefix = 'testForMemcachedClient.';


    protected function buildMock()
    {
        $cache = $this->getMock(
            '\Memcached',
            null
        );

        return $cache;
    }


    protected function buildMockWithServerConnected()
    {
        $cache = $this->buildMock();

        $cache->addServer(
            $this->memcachedServerHost,
            $this->memcachedServerPort
        );
        $cache->setOption(\Memcached::OPT_PREFIX_KEY, $this->keyPrefix);

        return $cache;
    }


    public function testAddServer()
    {
        $cache = $this->buildMock();

        $server= array(
            'host'   => $this->memcachedServerHost,
            'port'   => $this->memcachedServerPort,
            'weight' => 0
        );
        $key = "{$this->memcachedServerHost}:{$this->memcachedServerPort}:0";

        // First time add server
        $result = $cache->addServer(
            $this->memcachedServerHost,
            $this->memcachedServerPort
        );
        $this->assertTrue($result);
        $this->assertEquals(
            var_export(array($key => $server), true),
            var_export($cache->getServerList(), true)
        );

        // Add duplicate server
        $result = $cache->addServer(
            $this->memcachedServerHost,
            $this->memcachedServerPort
        );
        $this->assertFalse($result);
        $this->assertEquals(
            var_export(array($key => $server), true),
            var_export($cache->getServerList(), true)
        );
    }


    public function testAddServers()
    {
        $cache = $this->buildMock();

        $server= array(
            'host'   => $this->memcachedServerHost,
        );
        $key = "{$this->memcachedServerHost}:{$this->memcachedServerPort}:0";

        $cache->addServers(array($server));
        $cache->addServers(array($server, $server));

        $server['port'] = $this->memcachedServerPort;
        $server['weight'] = 0;
        $this->assertEquals(
            var_export(array($key => $server), true),
            var_export($cache->getServerList(), true)
        );
    }


    public function testConnectFail()
    {
        $cache = $this->buildMock();

        // Set to a false server
        $server= array(
            'host'   => '127.0.0.7',
            'port'   => $this->memcachedServerPort,
            'weight' => 0
        );

        $cache->addServer($server['host'], $server['port'], $server['weight']);

        $this->assertEquals(\Memcached::RES_FAILURE, $cache->getResultCode());
        $this->assertEquals('No server avaliable.', $cache->getResultMessage());
    }


    public function testDelete()
    {
        $cache = $this->buildMockWithServerConnected();

        $cache->set('foo', 'bar');
        $this->assertEquals('bar', $cache->get('foo'));

        $cache->delete('foo');
        $this->assertEmpty($cache->get('foo'));

        $cache->delete('not exists');
        $this->assertEquals(
            \Memcached::RES_NOTFOUND,
            $cache->getResultCode()
        );
    }


    public function testGetKey()
    {
        $cache = $this->buildMockWithServerConnected();

        $this->assertEquals(
            $this->keyPrefix . 'foobar',
            $cache->getKey('foobar')
        );
    }


    public function testGetOption()
    {
        $cache = $this->buildMockWithServerConnected();

        $this->assertEquals(
            $this->keyPrefix,
            $cache->getOption(\Memcached::OPT_PREFIX_KEY)
        );

        // Get option fail
        $this->assertFalse($cache->getOption('not exists'));
        $this->assertEquals(
            \Memcached::RES_FAILURE,
            $cache->getResultCode()
        );
    }


    public function testGetWithoutServerConnected()
    {
        $cache = $this->buildMock();

        $this->assertFalse($cache->get('anything'));
    }


    public function testSet()
    {
        $cache = $this->buildMockWithServerConnected();

        // Integer
        $i = mt_rand(0, 50000);
        $cache->set('foo', $i);
        $this->assertEquals($i, $cache->get('foo'));

        // String
        $s = 'foobar' . mt_rand(0, 50000);
        $cache->set('foo', $s);
        $this->assertEquals($s, $cache->get('foo'));

        // Array
        $ar = array(
            'bar1' => mt_rand(0, 50000),
            'bar2' => mt_rand(0, 50000),
        );
        $cache->set('foo', $ar);
        $this->assertEquals($ar, $cache->get('foo'));

        // Object
        $obj = new \stdClass;
        $obj->bar = mt_rand(0, 50000);
        $cache->set('foo', $obj);
        $this->assertEquals($obj, $cache->get('foo'));

        $this->assertEquals(
            \Memcached::RES_SUCCESS,
            $cache->getResultCode()
        );

        // Set fail
        $cache = $this->buildMock();
        $cache->set('foo', 'bar');
        $this->assertEquals(
            \Memcached::RES_FAILURE,
            $cache->getResultCode()
        );
    }


    public function testSetOptions()
    {
        $cache = $this->buildMock();

        $cache->setOptions(
            array(
                'foo' => 1,
                'bar' => 'b',
            )
        );

        $this->assertEquals(1, $cache->getOption('foo'));
        $this->assertEquals('b', $cache->getOption('bar'));
    }


    public function testIncrement()
    {
        $cache = $this->buildMockWithServerConnected();

        // Integer, default offset
        $i = mt_rand(0, 50000);
        $cache->set('foo', $i);
        $iRes = $cache->increment('foo');
        $this->assertEquals($i + 1, $iRes);
        $this->assertEquals($i + 1, $cache->get('foo'));

        // Integer, random offset
        $i2 = mt_rand(0, 50000);
        $offset = mt_rand(0, 500);
        $cache->set('foo', $i2);
        $i2Res = $cache->increment('foo', $offset);
        $this->assertEquals($i2 + $offset, $i2Res);
        $this->assertEquals($i2 + $offset, $cache->get('foo'));

        // Integer, default initial value
        $cache->delete('bar');
        $newKeyRes = $cache->increment('bar');
        $this->assertEquals(0, $newKeyRes);

        // Integer, random initial value
        $cache->delete('baz');
        $initialValue = mt_rand(0, 500);
        $offset2 = mt_rand(0, 500);
        $newKey2Res = $cache->increment('baz', $offset2, $initialValue);
        $this->assertEquals($initialValue, $newKey2Res);

        // String
        $s = 'foobar' . mt_rand(0, 50000);
        $cache->set('foo', $s);
        $sRes = $cache->increment('foo');
        $this->assertFalse($sRes);

        // Array
        $ar = array(
            'bar1' => mt_rand(0, 50000),
            'bar2' => mt_rand(0, 50000),
        );
        $cache->set('foo', $ar);
        $arRes = $cache->increment('foo');
        $this->assertFalse($arRes);

        // Object
        $obj = new \stdClass;
        $obj->bar = mt_rand(0, 50000);
        $cache->set('foo', $obj);
        $objRes = $cache->increment('foo');
        $this->assertFalse($objRes);
    }
}
