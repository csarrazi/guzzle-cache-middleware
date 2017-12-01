<?php

/*
 * This file is part of the CsaGuzzleBundle package
 *
 * (c) Charles Sarrazin <charles@sarraz.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Csa\Tests\GuzzleHttp\Middleware\Cache;

use Csa\GuzzleHttp\Middleware\Cache\Adapter\StorageAdapterInterface;
use Csa\GuzzleHttp\Middleware\Cache\MockMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

class MockMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function testRecord()
    {
        $response = new Response(204);
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);

        $adapter = $this->getMock(StorageAdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->isInstanceOf(RequestInterface::class),
                $this->equalTo($response)
            )
        ;

        $handler->push(new MockMiddleware($adapter, 'record'));

        $client = new Client(['handler' => $handler]);

        $client->get('http://foo.bar');
    }

    public function testReplay()
    {
        $response = new Response(204);
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);

        $adapter = $this->getMock(StorageAdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('fetch')
            ->with($this->isInstanceOf(RequestInterface::class))
            ->willReturn($response)
        ;

        $handler->push(new MockMiddleware($adapter, 'replay'));

        $client = new Client(['handler' => $handler]);

        $client->get('http://foo.bar');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Record not found for request: GET http://foo.bar
     */
    public function testReplayFailsWithoutMock()
    {
        $handler = HandlerStack::create();

        $adapter = $this->getMock(StorageAdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('fetch')
            ->with($this->isInstanceOf(RequestInterface::class))
        ;

        $handler->push(new MockMiddleware($adapter, 'replay'));

        $client = new Client(['handler' => $handler]);

        $client->get('http://foo.bar');
    }
}
