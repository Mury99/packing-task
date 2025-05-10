<?php

declare(strict_types=1);

namespace App\Infrastructure\Bin3DPacking\Logger;

use Psr\Log\LoggerInterface;

readonly class PackingErrorLogger
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $errors
     */
    public function log(array $errors): void
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
            'critical' => $this->logger->critical($message),
            'warning' => $this->logger->warning($message),
            'notice' => $this->logger->notice($message),
            default => $this->logger->error($message),
        };
    }
}
