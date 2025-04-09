<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Chain\PackingStrategyChain;
use App\Dto\PackageOutputDto;
use App\Dto\ProductDto;
use App\Service\PackingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class PackingServiceTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $cache;

    private MockObject $packingStrategyChain;

    private PackingService $packingService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->packingStrategyChain = $this->createMock(PackingStrategyChain::class);

        $this->packingService = new PackingService(
            $this->entityManager,
            $this->cache,
            $this->packingStrategyChain
        );
    }

    public function testCalculateBoxReturnsCachedResult(): void
    {
        $products = [new ProductDto(1, 10, 10, 10, 20)];
        $packageOutputDto = new PackageOutputDto(15, 15, 15, 20);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn($packageOutputDto);

        $this->cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $result = $this->packingService->calculateBox($products);

        $this->assertSame($packageOutputDto, $result);
    }

    /// ... more
}
