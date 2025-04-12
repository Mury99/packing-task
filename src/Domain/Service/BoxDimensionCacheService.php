<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Application\Dto\ProductDto;

final class BoxDimensionCacheService
{
    private const string SEPARATOR_COMMA = ',';

    private const string SEPARATOR_SEMICOLON = ';';

    /**
     * @param ProductDTO[] $products
     */
    public function createCacheKey(array $products): string
    {
        $normalizedBoxes = $this->normalizeBoxes($products);

        $keyParts = array_map(fn ($box) => implode(self::SEPARATOR_COMMA, $box), $normalizedBoxes);

        return sprintf('packing_%s', sha1(implode(self::SEPARATOR_SEMICOLON, $keyParts)));
    }

    /**
     * @param array{float, float, float} $dimensions
     * @return array<int, float>
     */
    private function normalizeBox(array $dimensions): array
    {
        sort($dimensions);

        return $dimensions;
    }

    /**
     * @param ProductDTO[] $products
     * @return list<array<int, float>>
     */
    private function normalizeBoxes(array $products): array
    {
        $normalized = array_map(fn (ProductDTO $product) => $this->normalizeBox($product->getDimensions()), $products);

        usort($normalized, fn ($a, $b) => strcmp(implode(self::SEPARATOR_COMMA, $a), implode(self::SEPARATOR_COMMA, $b)));

        return $normalized;
    }
}
