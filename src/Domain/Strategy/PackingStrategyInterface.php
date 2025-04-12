<?php

declare(strict_types=1);

namespace App\Domain\Strategy;

use App\Application\Dto\ProductDto;
use App\Domain\Entity\Packaging;
use App\Domain\Exception\PackingException;

interface PackingStrategyInterface
{
    /**
     * @param ProductDto[] $products
     *
     * @throws PackingException
     */
    public function pack(array $products): Packaging;
}
