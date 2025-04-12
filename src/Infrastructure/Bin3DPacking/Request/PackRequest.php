<?php

declare(strict_types=1);

namespace App\Infrastructure\Bin3DPacking\Request;

use App\Application\Dto\ProductDto;
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
