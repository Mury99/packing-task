<?php

declare(strict_types=1);

namespace App\Chain;

use App\Dto\PackageOutputDto;
use App\Dto\ProductDto;
use App\Exception\MultipleBinsNotSupportedException;
use App\Exception\PackingException;
use App\Exception\PackingStrategyNotApplicableException;
use App\Exception\SuitablePackageNotFoundException;
use App\Strategy\PackingStrategyInterface;

readonly class PackingStrategyChain
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
    public function chain(array $products): PackageOutputDto
    {
        foreach ($this->strategies as $strategy) {
            try {
                return $strategy->pack($products);
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
