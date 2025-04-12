<?php

declare(strict_types=1);

namespace App\Infrastructure\Bin3DPacking\Client;

use App\Infrastructure\Bin3DPacking\Dto\BinPackingShipmentDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BinPacking3DClient
{
    private const string PACK_A_SHIPMENT_ENDPOINT = '/packer/packIntoMany';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SerializerInterface $serializer,
        private readonly string $baseUrl,
        private readonly string $apiUsername,
        private readonly string $apiKey,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function packShipment(BinPackingShipmentDto $dto): ResponseInterface
    {
        $url = $this->baseUrl . self::PACK_A_SHIPMENT_ENDPOINT;
        $dto->withAuth($this->apiUsername, $this->apiKey);

        return $this->httpClient->request(Request::METHOD_POST, $url, [
            'body' => $this->serializer->serialize($dto, 'json'),
        ]);
    }
}
