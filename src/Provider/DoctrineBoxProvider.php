<?php

declare(strict_types=1);

namespace App\Provider;

use App\Entity\Packaging;
use Doctrine\ORM\EntityManagerInterface;

readonly class DoctrineBoxProvider implements BoxProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getBoxes(): array
    {
        $qb = $this->entityManager->getRepository(Packaging::class)->createQueryBuilder('p');
        $qb
            ->select('p.width, p.height, p.length, p.maxWeight, MAX(p.id) as id') // makes sense a bit in terms of "multiple bins not supported"
            ->groupBy('p.width, p.height, p.length, p.maxWeight');

        $distinctBoxes = $qb->getQuery()->getArrayResult();

        return array_map(fn (array $packaging) => [
            'id' => $packaging['id'],
            'width' => $packaging['width'],
            'height' => $packaging['height'],
            'length' => $packaging['length'],
            'maxWeight' => $packaging['maxWeight'],
        ], $distinctBoxes);
    }
}
