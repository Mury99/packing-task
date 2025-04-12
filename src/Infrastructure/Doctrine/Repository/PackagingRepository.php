<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Packaging;
use App\Domain\Repository\PackagingRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class PackagingRepository implements PackagingRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(int $id): ?Packaging
    {
        return $this->entityManager->getRepository(Packaging::class)->find($id);
    }

    public function findByDimensions(float $width, float $height, float $length, float $maxWeight): ?Packaging
    {
        return $this->entityManager->getRepository(Packaging::class)
            ->findOneBy([
                'width' => $width,
                'height' => $height,
                'length' => $length,
                'max_weight' => $maxWeight,
            ]);
    }
}
