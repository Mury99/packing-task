<?php

declare(strict_types=1);

namespace App\Infrastructure\Bin3DPacking\Dto;

readonly class PackedBin
{
    public function __construct(
        public int $id,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
