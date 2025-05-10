<?php

declare(strict_types=1);

namespace App\Application\Strategy;

use App\Domain\Entity\Packaging;
use App\Domain\Exception\PackingException;
use App\Domain\Exception\SuitablePackageNotFoundException;
use App\Domain\Provider\BoxProviderInterface;
use App\Domain\Repository\PackagingRepositoryInterface;
use App\Domain\Strategy\PackingStrategyInterface;
use App\Infrastructure\Bin3DPacking\Client\BinPacking3DClient;
use App\Infrastructure\Bin3DPacking\Factory\BinPackingShipmentPayloadFactory;
use App\Infrastructure\Bin3DPacking\Handler\PackingResponseHandler;
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
        private PackagingRepositoryInterface $packagingRepository,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function pack(array $products): Packaging
    {
        $boxes = $this->boxProvider->getBoxes();
        $payload = $this->payloadFactory->create($boxes, $products);

        try {
            $response = $this->packingClient->packShipment($payload)->toArray();
            $packedBin = $this->responseHandler->handle($response['response']);
            $packaging = $this->packagingRepository->findById($packedBin->getId());

            if ($packaging === null) {
                throw new SuitablePackageNotFoundException('No packaging found for the selected box dimensions');
            }

            return $packaging;
        } catch (TransportExceptionInterface|HttpExceptionInterface $e) {
            $this->logger?->error(sprintf('Transport/HTTP error: %s', $e->getMessage()));

            throw new PackingException('Packing API communication error', 0, $e);
        } catch (PackingException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger?->error(sprintf('Unexpected packing failure: %s', $e->getMessage()));

            throw new PackingException('Unexpected packing failure', 0, $e);
        }
    }
}
