<?php

declare(strict_types=1);

namespace App\Handler;

use App\Dto\PackageOutputDto;
use App\Exception\MultipleBinsNotSupportedException;
use App\Exception\PackingException;
use App\Exception\SuitablePackageNotFoundException;
use Psr\Log\LoggerInterface;

readonly class PackingResponseHandler
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @throws MultipleBinsNotSupportedException
     * @throws PackingException
     * @throws SuitablePackageNotFoundException
     *
     * @param array<string, mixed> $response
     */
    public function handle(array $response): PackageOutputDto
    {
        $this->logErrorsIfAny($response['errors']);
        $status = $response['status'];

        $this->handleNotPackedItems($response['not_packed_items']);

        return match ($status) {
            0 => throw new PackingException('Critical error occurred during packing'),
            1 => $this->buildOutput($response['bins_packed']),
            default => throw new PackingException(sprintf('Unhandled response status: %s', $status)),
        };
    }

    /**
     * @throws MultipleBinsNotSupportedException
     * @throws SuitablePackageNotFoundException
     *
     * @param array<int, mixed> $bins
     */
    private function buildOutput(array $bins): PackageOutputDto
    {
        return match (count($bins)) {
            0 => throw new SuitablePackageNotFoundException(),
            1 => $this->createDto($bins[0]),
            default => throw new MultipleBinsNotSupportedException(sprintf(
                'Multiple bins (%d) were returned, but only one bin is supported at the moment',
                count($bins)
            )),
        };
    }

    /**
     * @param array<string, mixed> $bin
     */
    private function createDto(array $bin): PackageOutputDto
    {
        $data = $bin['bin_data'];

        return new PackageOutputDto(
            $data['w'],
            $data['h'],
            $data['d'],
            $data['weight'],
        );
    }

    /**
     * @param array<string, mixed> $errors
     */
    private function logErrorsIfAny(array $errors): void
    {
        foreach ($errors as $error) {
            $this->logError($error);
        }
    }

    /**
     * @param array<string, mixed> $error
     */
    private function logError(array $error): void
    {
        $level = $error['level'];
        $message = sprintf('Packing error [%s]: %s', $level, $error['message']);

        match ($level) {
            'critical' => $this->logger?->critical($message),
            'warning' => $this->logger?->warning($message),
            'notice' => $this->logger?->notice($message),
            default => $this->logger?->error($message),
        };
    }

    /**
     * @throws SuitablePackageNotFoundException
     *
     * @param array<array{id: string, w: float|int, h: float|int, d: float|int, q: float|int}> $notPackedItems
     */
    private function handleNotPackedItems(array $notPackedItems): void
    {
        foreach ($notPackedItems as $item) {
            throw new SuitablePackageNotFoundException(
                sprintf('Product %s cannot be packed into any bin due to size/weight limitations.', $item['id']),
            );
        }
    }
}
