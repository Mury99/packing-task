<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PackRequest
{
    /**
     * @param ProductDto[] $products
     */
    public function __construct(
        #[Assert\Valid]
        #[Assert\Count(min: 1)]
        public array $products,
    ) {
    }
}
