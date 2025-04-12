<?php

declare(strict_types=1);

namespace App\Application\Strategy;

use App\Domain\Entity\Packaging;
use App\Domain\Service\BoxDimensionCacheService;
use App\Domain\Strategy\PackingStrategyInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

readonly class CacheablePackingStrategy implements PackingStrategyInterface
{
    private const int CACHE_EXPIRATION_SECONDS = 20; // 86400

    public function __construct(
        private PackingStrategyInterface $packingStrategy,
        private BoxDimensionCacheService $boxDimensionCacheService,
        private CacheItemPoolInterface $cache,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function pack(array $products): Packaging
    {
        $dimensionsHash = $this->boxDimensionCacheService->createCacheKey($products);

        $cacheItem = $this->cache->getItem($dimensionsHash);
        if ($cacheItem->isHit()) {
            $this->logger?->info('cached');

            return $cacheItem->get();
        }

        $result = $this->packingStrategy->pack($products);

        $cacheItem->set($result)->expiresAfter(self::CACHE_EXPIRATION_SECONDS);
        $this->cache->save($cacheItem);

        return $result;
    }
}
