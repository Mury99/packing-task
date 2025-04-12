<?php

declare(strict_types=1);

namespace App\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ProductDto
{
    public function __construct(
        #[Assert\Positive]
        #[Assert\NotBlank]
        public int $id,
        #[Assert\Positive]
        #[Assert\NotBlank]
        public float $width,
        #[Assert\Positive]
        #[Assert\NotBlank]
        public float $height,
        #[Assert\Positive]
        #[Assert\NotBlank]
        public float $length,
        #[Assert\Positive]
        #[Assert\NotBlank]
        public float $weight,
    ) {
    }

    /**
     * @return array{float, float, float}
     */
    public function getDimensions(): array
    {
        return [$this->width, $this->height, $this->length];
    }
}
