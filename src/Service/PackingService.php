<?php

declare(strict_types=1);

namespace App\Service;

use App\Chain\PackingStrategyChain;
use App\Dto\PackageOutputDto;
use App\Dto\ProductDto;
use App\Entity\PackingResult;
use App\Exception\MultipleBinsNotSupportedException;
use App\Exception\PackingStrategyNotApplicableException;
use App\Exception\SuitablePackageNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

readonly class PackingService
{
    private const int CACHE_EXPIRATION_SECONDS = 10; // 86400

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheItemPoolInterface $cache,
        private PackingStrategyChain $packingStrategyChain,
    ) {
    }

    /**
     * @param ProductDto[] $products
     *
     * @throws MultipleBinsNotSupportedException
     * @throws SuitablePackageNotFoundException
     * @throws PackingStrategyNotApplicableException
     * @throws \JsonException
     * @throws InvalidArgumentException
     */
    public function calculateBox(array $products): PackageOutputDto
    {
        $inputHash = md5(json_encode($products, JSON_THROW_ON_ERROR));

        $cacheItem = $this->cache->getItem($inputHash);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $packingResult = $this->entityManager->getRepository(PackingResult::class)
            ->findOneBy([
                'inputHash' => $inputHash,
            ]);

        if ($packingResult !== null) {
            $result = unserialize($packingResult->getResult());

            $this->storeInCache($cacheItem, $result);

            return $result;
        }

        $result = $this->packingStrategyChain->chain($products);

        $packingResult = new PackingResult($inputHash, serialize($result));
        $this->entityManager->persist($packingResult);
        $this->entityManager->flush();

        $this->storeInCache($cacheItem, $result);

        return $result;
    }

    private function storeInCache(CacheItemInterface $cacheItem, PackageOutputDto $result): void
    {
        $cacheItem->set($result)->expiresAfter(self::CACHE_EXPIRATION_SECONDS);
        $this->cache->save($cacheItem);
    }
}
