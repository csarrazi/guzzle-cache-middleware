<?php

/*
 * This file is part of the CsaGuzzleBundle package
 *
 * (c) Charles Sarrazin <charles@sarraz.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Csa\Tests\GuzzleHttp\Middleware\Cache\Adapter;

use Csa\GuzzleHttp\Middleware\Cache\Adapter\DoctrineAdapter;
use Csa\GuzzleHttp\Middleware\Cache\NamingStrategy\NamingStrategyInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class DoctrineAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $class = DoctrineAdapter::class;

    public function testConstructor()
    {
        $cache = $this->getMock('Doctrine\Common\Cache\Cache');
        new $this->class($cache, 0);
    }

    public function testFetch()
    {
        $cache = $this->getMock('Doctrine\Common\Cache\Cache');

        $cache
            ->expects($this->at(0))
            ->method('contains')
            ->willReturn(false)
        ;
        $cache
            ->expects($this->at(1))
            ->method('contains')
            ->willReturn(true)
        ;
        $cache
            ->expects($this->at(2))
            ->method('fetch')
            ->willReturn([
                'status' => 200,
                'headers' => [],
                'body' => 'Hello World',
                'version' => '1.1',
                'reason' => 'OK',
            ])
        ;
        $adapter = new $this->class($cache, 0);

        $request = $this->getRequestMock();

        $this->assertNull($adapter->fetch($request));
        $this->assertInstanceOf(ResponseInterface::class, $adapter->fetch($request));
    }

    public function testSave()
    {
        $cache = $this->getMock('Doctrine\Common\Cache\Cache');

        $cache
            ->expects($this->at(0))
            ->method('save')
            ->with(
                $this->isType('string'),
                [
                    'status' => 200,
                    'headers' => [],
                    'body' => 'Hello World',
                    'version' => '1.1',
                    'reason' => 'OK',
                ],
                10
            );
        $adapter = new $this->class($cache, 10);
        $adapter->save($this->getRequestMock(), $this->getResponseMock());
    }

    public function testFetchWithInjectedNamingStrategy()
    {
        $cache = $this->getMock('Doctrine\Common\Cache\Cache');
        $namingStrategy = $this->getMock(NamingStrategyInterface::class);
        $request = $this->getRequestMock();
        $adapter = new $this->class($cache, 0, $namingStrategy);

        $namingStrategy->expects($this->once())->method('filename')->with($request);

        $adapter->fetch($request);
    }

    public function testSaveWithInjectedNamingStrategy()
    {
        $cache = $this->getMock('Doctrine\Common\Cache\Cache');
        $namingStrategy = $this->getMock(NamingStrategyInterface::class);
        $request = $this->getRequestMock();
        $response = $this->getResponseMock();
        $adapter = new $this->class($cache, 0, $namingStrategy);

        $namingStrategy->expects($this->once())->method('filename')->with($request);

        $adapter->save($request, $response);
    }

    private function getRequestMock()
    {
        return new Request('GET', 'http://google.com/', ['Accept' => 'text/html']);
    }

    private function getResponseMock()
    {
        return new Response(200, [], 'Hello World');
    }
}
