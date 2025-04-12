<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Entity\Packaging;

class PackageOutputDto
{
    public function __construct(
        public float $width,
        public float $height,
        public float $length,
        public float $weight,
    ) {
    }

    public static function createFromPacking(Packaging $packaging): self
    {
        return new self(
            $packaging->getWidth(),
            $packaging->getHeight(),
            $packaging->getLength(),
            $packaging->getMaxWeight()
        );
    }
}
