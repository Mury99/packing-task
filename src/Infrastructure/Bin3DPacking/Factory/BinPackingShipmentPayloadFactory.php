<?php

declare(strict_types=1);

namespace App\Infrastructure\Bin3DPacking\Factory;

use App\Application\Dto\ProductDto;
use App\Infrastructure\Bin3DPacking\Dto\BinPackingShipmentDto;

class BinPackingShipmentPayloadFactory
{
    private const int DEFAULT_QUANTITY = 1;

    /**
     * @param array<array{id: int, width: float, height: float, length: float, maxWeight: float}> $bins
     * @param ProductDto[] $items
     * @param array<string, int|string> $params
     */
    public function create(array $bins, array $items, array $params = []): BinPackingShipmentDto
    {
        $binsArray = $this->transformBins($bins);
        $itemsArray = $this->transformItems($items);

        return new BinPackingShipmentDto($binsArray, $itemsArray, $params);
    }

    /**
     * @param array<array{id: int, width: float, height: float, length: float, maxWeight: float}> $bins
     * @return array<int, array{id: string, w: float|int, h: float|int, d: float|int, max_wg: float|int}>
     */
    private function transformBins(array $bins): array
    {
        return array_map(fn (array $bin) => [
            'id' => (string) $bin['id'],
            'w' => $bin['width'],
            'h' => $bin['height'],
            'd' => $bin['length'],
            'max_wg' => $bin['maxWeight'],
        ], $bins);
    }

    /**
     * @param ProductDto[] $items
     * @return array<int, array{id: string, w: float|int, h: float|int, d: float|int, wg: float|int, q: int}>
     */
    private function transformItems(array $items): array
    {
        return array_map(fn (ProductDto $product) => [
            'id' => (string) $product->id,
            'w' => $product->width,
            'h' => $product->height,
            'd' => $product->length,
            'wg' => $product->weight,
            'q' => self::DEFAULT_QUANTITY, // DOC: Input only: width, height, length, weight
        ], $items);
    }
}
