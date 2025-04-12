<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Provider;

use App\Domain\Entity\Packaging;
use App\Domain\Provider\BoxProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class DoctrineBoxProvider implements BoxProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getBoxes(): array
    {
        $boxes = $this->entityManager->getRepository(Packaging::class)->findAll();

        return array_map(fn (Packaging $packaging) => [
            'id' => (int) $packaging->getId(),
            'width' => $packaging->getWidth(),
            'height' => $packaging->getHeight(),
            'length' => $packaging->getLength(),
            'maxWeight' => $packaging->getMaxWeight(),
        ], $boxes);
    }
}
