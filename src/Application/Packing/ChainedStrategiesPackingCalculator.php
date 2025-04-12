<?php

declare(strict_types=1);

namespace App\Application\Packing;

use App\Application\Dto\PackageOutputDto;
use App\Application\Dto\ProductDto;
use App\Domain\Exception\MultipleBinsNotSupportedException;
use App\Domain\Exception\PackingException;
use App\Domain\Exception\PackingStrategyNotApplicableException;
use App\Domain\Exception\SuitablePackageNotFoundException;
use App\Domain\Packing\PackingCalculatorInterface;
use App\Domain\Strategy\PackingStrategyInterface;

readonly class ChainedStrategiesPackingCalculator implements PackingCalculatorInterface
{
    /**
     * @param PackingStrategyInterface[] $strategies
     */
    public function __construct(
        private iterable $strategies,
    ) {
    }

    /**
     * @param ProductDto[] $products
     *
     * @throws MultipleBinsNotSupportedException
     * @throws PackingStrategyNotApplicableException
     * @throws SuitablePackageNotFoundException
     */
    public function calculate(array $products): PackageOutputDto
    {
        foreach ($this->strategies as $strategy) {
            try {
                $packing = $strategy->pack($products);

                return PackageOutputDto::createFromPacking($packing);
            } catch (SuitablePackageNotFoundException|MultipleBinsNotSupportedException $e) {
                throw $e;
            } catch (PackingException) {
                continue;
            }
        }

        throw new PackingStrategyNotApplicableException(
            'All packing strategies have been exhausted and none were successful'
        );
    }
}
