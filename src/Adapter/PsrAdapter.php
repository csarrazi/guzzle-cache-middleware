<?php

/*
 * This file is part of the CsaGuzzleBundle package
 *
 * (c) Charles Sarrazin <charles@sarraz.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Csa\GuzzleHttp\Middleware\Cache\Adapter;

use Csa\GuzzleHttp\Middleware\Cache\NamingStrategy\HashNamingStrategy;
use Csa\GuzzleHttp\Middleware\Cache\NamingStrategy\NamingStrategyInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PsrAdapter implements StorageAdapterInterface
{
    private $cache;
    private $namingStrategy;
    private $ttl;

    /**
     * @param CacheItemPoolInterface $cache
     * @param int $ttl
     * @param NamingStrategyInterface|null $namingStrategy
     */
    public function __construct(CacheItemPoolInterface $cache, $ttl = 0, NamingStrategyInterface $namingStrategy = null)
    {
        $this->cache = $cache;
        $this->namingStrategy = $namingStrategy ?: new HashNamingStrategy();
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(RequestInterface $request)
    {
        $key = $this->namingStrategy->filename($request);

        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            $data = $item->get();

            return new Response($data['status'], $data['headers'], $data['body'], $data['version'], $data['reason']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(RequestInterface $request, ResponseInterface $response)
    {
        $key = $this->namingStrategy->filename($request);

        $item = $this->cache->getItem($key);
        $item->expiresAfter($this->ttl);
        $item->set([
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody(),
            'version' => $response->getProtocolVersion(),
            'reason' => $response->getReasonPhrase(),
        ]);

        $this->cache->save($item);

        $response->getBody()->seek(0);
    }
}
