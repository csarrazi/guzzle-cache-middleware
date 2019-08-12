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
use Doctrine\Common\Cache\Cache;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DoctrineAdapter implements StorageAdapterInterface
{
    private $cache;
    private $namingStrategy;
    private $ttl;

    /**
     * @param Cache $cache
     * @param int $ttl
     * @param NamingStrategyInterface|null $namingStrategy
     */
    public function __construct(Cache $cache, $ttl = 0, NamingStrategyInterface $namingStrategy = null)
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

        if ($this->cache->contains($key)) {
            $data = $this->cache->fetch($key);

            return new Response($data['status'], $data['headers'], $data['body'], $data['version'], $data['reason']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(RequestInterface $request, ResponseInterface $response)
    {
        $data = [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string)$response->getBody(),
            'version' => $response->getProtocolVersion(),
            'reason' => $response->getReasonPhrase(),
        ];

        $this->cache->save($this->namingStrategy->filename($request), $data, $this->ttl);

        $response->getBody()->seek(0);
    }
}
