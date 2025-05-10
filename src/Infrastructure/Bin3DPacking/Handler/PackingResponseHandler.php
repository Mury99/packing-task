<?php

declare(strict_types=1);

namespace App\Infrastructure\Bin3DPacking\Handler;

use App\Domain\Exception\MultipleBinsNotSupportedException;
use App\Domain\Exception\PackingException;
use App\Domain\Exception\SuitablePackageNotFoundException;
use App\Infrastructure\Bin3DPacking\Dto\PackedBin;
use App\Infrastructure\Bin3DPacking\Logger\PackingErrorLogger;

readonly class PackingResponseHandler
{
    public function __construct(
        private PackingErrorLogger $errorLogger,
    ) {
    }

    /**
     * @param array<string, mixed> $response
     * @throws PackingException
     * @throws SuitablePackageNotFoundException
     *
     * @throws MultipleBinsNotSupportedException
     */
    public function handle(array $response): PackedBin
    {
        $this->errorLogger->log($response['errors']);
        $status = $response['status'];

        $this->handleNotPackedItems($response['not_packed_items']);

        return match ($status) {
            0 => throw new PackingException('Critical error occurred during packing'),
            1 => $this->buildOutput($response['bins_packed']),
            default => throw new PackingException(sprintf('Unhandled response status: %s', $status)),
        };
    }

    /**
     * @param array<int, mixed> $bins
     * @throws SuitablePackageNotFoundException
     *
     * @throws MultipleBinsNotSupportedException
     */
    private function buildOutput(array $bins): PackedBin
    {
        return match (count($bins)) {
            0 => throw new SuitablePackageNotFoundException(),
            1 => new PackedBin($bins[0]['bin_data']['id']),
            default => throw new MultipleBinsNotSupportedException(sprintf(
                'Multiple bins (%d) were returned, but only one bin is supported at the moment',
                count($bins)
            )),
        };
    }

    /**
     * @param array<array{id: string, w: float|int, h: float|int, d: float|int, q: float|int}> $notPackedItems
     * @throws SuitablePackageNotFoundException
     */
    private function handleNotPackedItems(array $notPackedItems): void
    {
        foreach ($notPackedItems as $item) {
            throw new SuitablePackageNotFoundException(
                sprintf('Product ID: %s - cannot be packed into any bin due to size/weight limitations.', $item['id']),
            );
        }
    }
}
