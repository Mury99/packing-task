<?php

declare(strict_types=1);

namespace App\Application\Strategy;

use App\Domain\Entity\Packaging;
use App\Domain\Exception\SuitablePackageNotFoundException;
use App\Domain\Provider\BoxProviderInterface;
use App\Domain\Repository\PackagingRepositoryInterface;
use App\Domain\Strategy\PackingStrategyInterface;

readonly class SimpleFallbackPackingStrategy implements PackingStrategyInterface
{
    public function __construct(
        private BoxProviderInterface $boxProvider,
        private PackagingRepositoryInterface $packagingRepository,
    ) {
    }

    public function pack(array $products): Packaging
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
            $totalLength += $product->length;
            $totalWeight += $product->weight;
        }

        $suitableBoxes = $this->findSuitableBoxes($boxes, $totalWidth, $totalHeight, $totalLength, $totalWeight);

        if (count($suitableBoxes) === 0) {
            throw new SuitablePackageNotFoundException();
        }

        usort(
            $suitableBoxes,
            fn (array $a, array $b) => $this->compareBoxes($a, $b)
        );

        $finalBox = reset($suitableBoxes);

        $packaging = $this->packagingRepository->findByDimensions(
            $finalBox['width'],
            $finalBox['height'],
            $finalBox['length'],
            $finalBox['maxWeight']
        );

        if ($packaging === null) {
            throw new SuitablePackageNotFoundException('No packaging found for the selected box dimensions');
        }

        return $packaging;
    }

    /**
     * @param array<array{id: int, width: float|int, height: float|int, length: float|int, maxWeight: float|int}> $boxes
     * @return array<array{id: int, width: float|int, height: float|int, length: float|int, maxWeight: float|int}>
     */
    private function findSuitableBoxes(
        array $boxes,
        float $totalWidth,
        float $totalHeight,
        float $totalLength,
        float $totalWeight,
    ): array {
        return array_filter(
            $boxes,
            fn (array $box) =>
                $box['width'] >= $totalWidth &&
                $box['height'] >= $totalHeight &&
                $box['length'] >= $totalLength &&
                $box['maxWeight'] >= $totalWeight
        );
    }

    /**
     * @param array{id: int, width: float|int, height: float|int, length: float|int, maxWeight: float|int} $a
     * @param array{id: int, width: float|int, height: float|int, length: float|int, maxWeight: float|int} $b
     */
    private function compareBoxes(array $a, array $b): int
    {
        $volumeA = $a['width'] * $a['height'] * $a['length'];
        $volumeB = $b['width'] * $b['height'] * $b['length'];

        if ($volumeA === $volumeB) {
            return $b['maxWeight'] <=> $a['maxWeight'];
        }

        return $volumeA <=> $volumeB;
    }
}
