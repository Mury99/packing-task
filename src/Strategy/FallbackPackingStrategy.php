<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Dto\PackageOutputDto;
use App\Exception\SuitablePackageNotFoundException;
use App\Provider\BoxProviderInterface;

readonly class FallbackPackingStrategy implements PackingStrategyInterface
{
    public function __construct(
        private BoxProviderInterface $boxProvider,
    ) {
    }

    public function pack(array $products): PackageOutputDto
    {
        $boxes = $this->boxProvider->getBoxes();
        if (count($boxes) === 0) {
            throw new SuitablePackageNotFoundException('No box available');
        }

        $totalWidth = 0.0;
        $totalHeight = 0.0;
        $totalLength = 0.0;
        $totalWeight = 0.0;

        foreach ($products as $product) {
            $totalWidth += $product->width;
            $totalHeight += $product->height;
            $totalLength = max($totalLength, $product->length);
            $totalWeight += $product->weight;
        }

        $suitableBoxes = array_filter(
            $boxes,
            fn (array $box) =>
                $box['width'] >= $totalWidth &&
                $box['height'] >= $totalHeight &&
                $box['length'] >= $totalLength &&
                $box['maxWeight'] >= $totalWeight
        );

        if (count($suitableBoxes) === 0) {
            throw new SuitablePackageNotFoundException();
        }

        usort(
            $suitableBoxes,
            fn (array $a, array $b) => ($a['width'] * $a['height'] * $a['length']) <=> ($b['width'] * $b['height'] * $b['length'])
        );

        $finalBox = reset($suitableBoxes);

        return new PackageOutputDto(
            $finalBox['width'],
            $finalBox['height'],
            $finalBox['length'],
            $totalWeight
        );
    }
}
