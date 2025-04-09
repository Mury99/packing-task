<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Dto\PackageOutputDto;
use App\Dto\ProductDto;
use App\Exception\PackingException;

interface PackingStrategyInterface
{
    /**
     * @param ProductDto[] $products
     *
     * @throws PackingException
     */
    public function pack(array $products): PackageOutputDto;
}
