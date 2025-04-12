<?php

declare(strict_types=1);

namespace App\Domain\Provider;

interface BoxProviderInterface
{
    /**
     * @return array<array{id: int, width: float|int, height: float|int, length: float|int, maxWeight: float|int}>
     */
    public function getBoxes(): array;
}
