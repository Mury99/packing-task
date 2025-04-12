<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Packaging;
use App\Domain\Entity\PackingResult;

interface PackingResultRepositoryInterface
{
    public function findByHash(string $hash): ?PackingResult;

    public function save(string $hash, Packaging $packaging): void;
}
