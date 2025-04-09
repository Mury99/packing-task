<?php

declare(strict_types=1);

namespace App\Dto;

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
}
