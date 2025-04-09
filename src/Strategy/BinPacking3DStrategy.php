<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Client\BinPacking3DClient;
use App\Dto\PackageOutputDto;
use App\Exception\PackingException;
use App\Factory\BinPackingShipmentPayloadFactory;
use App\Handler\PackingResponseHandler;
use App\Provider\BoxProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

readonly class BinPacking3DStrategy implements PackingStrategyInterface
{
    public function __construct(
        private BinPacking3DClient $packingClient,
        private BoxProviderInterface $boxProvider,
        private BinPackingShipmentPayloadFactory $payloadFactory,
        private PackingResponseHandler $responseHandler,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function pack(array $products): PackageOutputDto
    {
        $boxes = $this->boxProvider->getBoxes();
        $payload = $this->payloadFactory->create($boxes, $products);

        try {
            $response = $this->packingClient->packShipment($payload)->toArray();

            return $this->responseHandler->handle($response['response']);
        } catch (TransportExceptionInterface|HttpExceptionInterface $e) {
            $this->logger?->error(sprintf('Transport/HTTP error: %s', $e->getMessage()));

            throw new PackingException('Packing API communication error', 0, $e);
        } catch (PackingException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger?->error(sprintf('Unexpected packing failure: %s', $e->getMessage()));

            throw new PackingException('Unexpected packing failure', 0, $e);
        }
    }
}
