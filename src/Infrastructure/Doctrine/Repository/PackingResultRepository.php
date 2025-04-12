<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Packaging;
use App\Domain\Entity\PackingResult;
use App\Domain\Repository\PackingResultRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class PackingResultRepository implements PackingResultRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findByHash(string $hash): ?PackingResult
    {
        return $this->entityManager->getRepository(PackingResult::class)
            ->findOneBy([
                'hash' => $hash,
            ]);
    }

    public function save(string $hash, Packaging $packaging): void
    {
        $packingResult = new PackingResult($hash, $packaging);

        $this->entityManager->persist($packingResult);
    }
}
