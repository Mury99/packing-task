<?php

declare(strict_types=1);

namespace App\Domain\Packing;

use App\Application\Dto\PackageOutputDto;
use App\Application\Dto\ProductDto;
use App\Domain\Exception\MultipleBinsNotSupportedException;
use App\Domain\Exception\PackingStrategyNotApplicableException;
use App\Domain\Exception\SuitablePackageNotFoundException;

interface PackingCalculatorInterface
{
    /**
     * @param ProductDto[] $products
     *
     * @throws MultipleBinsNotSupportedException
     * @throws PackingStrategyNotApplicableException
     * @throws SuitablePackageNotFoundException
     */
    public function calculate(array $products): PackageOutputDto;
}
