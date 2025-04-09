<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class BinPackingShipmentDto extends AbstractBinPackingDto
{
    /**
     * @param array<int, array{id: string, w: float|int, h: float|int, d: float|int, max_wg: float|int}> $bins
     * @param array<int, array{id: string, w: float|int, h: float|int, d: float|int, wg: float|int, q: int}> $items
     * @param array<string, int|string> $params
     */
    public function __construct(
        public array $bins,
        #[Assert\Count(max: 4999)]
        public array $items,
        public array $params = [],
    ) {
    }
}
