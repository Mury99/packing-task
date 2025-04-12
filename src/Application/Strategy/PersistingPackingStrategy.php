<?php

declare(strict_types=1);

namespace App\Application\Strategy;

use App\Domain\Entity\Packaging;
use App\Domain\Repository\PackingResultRepositoryInterface;
use App\Domain\Service\BoxDimensionCacheService;
use App\Domain\Strategy\PackingStrategyInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class PersistingPackingStrategy implements PackingStrategyInterface
{
    public function __construct(
        private PackingStrategyInterface $strategy,
        private BoxDimensionCacheService $boxDimensionCacheService,
        private PackingResultRepositoryInterface $repository,
        private EntityManagerInterface $entityManager,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function pack(array $products): Packaging
    {
        $dimensionsHash = $this->boxDimensionCacheService->createCacheKey($products);

        $packingResult = $this->repository->findByHash($dimensionsHash);
        if ($packingResult !== null) {
            $this->logger?->info('found in db');

            return $packingResult->getPackaging();
        }

        $result = $this->strategy->pack($products);

        $this->repository->save($dimensionsHash, $result);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $this->logger?->notice('Unique constraint triggered');
            $this->entityManager->clear();
        }

        return $result;
    }
}
