<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Bin3DPacking\Client;

use App\Infrastructure\Bin3DPacking\Client\BinPacking3DClient;
use App\Infrastructure\Bin3DPacking\Dto\BinPackingShipmentDto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BinPacking3DClientTest extends TestCase
{
    private string $baseUrl = 'https://api.example.com';

    private string $apiUsername = 'apiUser';

    private string $apiKey = 'apiKey';

    private MockObject $httpClient;

    private MockObject $serializer;

    private MockObject&BinPackingShipmentDto $dto;

    private BinPacking3DClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->dto = $this->createMock(BinPackingShipmentDto::class);

        $this->client = new BinPacking3DClient(
            $this->httpClient,
            $this->serializer,
            $this->baseUrl,
            $this->apiUsername,
            $this->apiKey
        );
    }

    public function testPackShipmentReturnsResponseSuccessfully(): void
    {
        $serializedJson = '{"test":"data"}';
        $response = $this->createMock(ResponseInterface::class);

        $this->dto->expects($this->once())
            ->method('withAuth')
            ->with($this->apiUsername, $this->apiKey);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($this->dto, 'json')
            ->willReturn($serializedJson);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(Request::METHOD_POST, $this->baseUrl . '/packer/packIntoMany', [
                'body' => $serializedJson,
            ])
            ->willReturn($response);

        $result = $this->client->packShipment($this->dto);

        $this->assertSame($response, $result);
    }

    public function testPackShipmentThrowsWhenHttpRequestFails(): void
    {
        $serializedJson = '{"fail":"case"}';

        $this->dto->expects($this->once())
            ->method('withAuth')
            ->with($this->apiUsername, $this->apiKey);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn($serializedJson);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $this->expectException(TransportExceptionInterface::class);

        $this->client->packShipment($this->dto);
    }
}
