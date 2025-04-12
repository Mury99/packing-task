<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Packaging;

interface PackagingRepositoryInterface
{
    public function findById(int $id): ?Packaging;

    public function findByDimensions(float $width, float $height, float $length, float $maxWeight): ?Packaging;
}
