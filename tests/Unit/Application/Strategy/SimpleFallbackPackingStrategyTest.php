<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Strategy;

use App\Application\Dto\ProductDto;
use App\Application\Strategy\SimpleFallbackPackingStrategy;
use App\Domain\Entity\Packaging;
use App\Domain\Exception\SuitablePackageNotFoundException;
use App\Domain\Provider\BoxProviderInterface;
use App\Domain\Repository\PackagingRepositoryInterface;
use App\Infrastructure\Bin3DPacking\Request\PackRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SimpleFallbackPackingStrategyTest extends TestCase
{
    #[DataProvider('packDataProviderHappyPaths')]
    #[DataProvider('packDataProviderUnhappyPaths')]
    public function testPackFindsSuitableBox(array $boxes, array $products, int|\Throwable $expected): void
    {
        $boxProvider = $this->createMock(BoxProviderInterface::class);
        $boxProvider->method('getBoxes')->willReturn($boxes);

        $packagingRepository = $this->createMock(PackagingRepositoryInterface::class);

        if (is_numeric($expected)) {
            $packagingRepository->expects($this->once())
                ->method('findByDimensions')
                ->with(
                    $boxes[$expected]['width'],
                    $boxes[$expected]['height'],
                    $boxes[$expected]['length'],
                    $boxes[$expected]['maxWeight']
                )
                ->willReturn($this->createMock(Packaging::class));
        }

        $packRequest = $this->createPackRequest($products);

        $strategy = new SimpleFallbackPackingStrategy($boxProvider, $packagingRepository);

        try {
            $strategy->pack($packRequest->products);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(\Throwable::class, $expected);
            $this->assertEquals($expected, $e);
        }
    }

    public static function packDataProviderHappyPaths(): \Generator
    {
        yield 'suitable box found' => [
            'boxes' => [
                [
                    'width' => 50,
                    'height' => 50,
                    'length' => 50,
                    'maxWeight' => 200,
                ],
                [
                    'width' => 20,
                    'height' => 30,
                    'length' => 20,
                    'maxWeight' => 120,
                ],
                [
                    'width' => 20,
                    'height' => 25,
                    'length' => 20,
                    'maxWeight' => 95,
                ],
            ],
            'products' => [
                [
                    'id' => 1,
                    'width' => 15,
                    'height' => 15,
                    'length' => 15,
                    'weight' => 45,
                ],
                [
                    'id' => 2,
                    'width' => 10,
                    'height' => 5,
                    'length' => 12,
                    'weight' => 55,
                ],
            ],
            'expected' => 0,
        ];

        yield 'multiple suitable boxes, smallest picked' => [
            'boxes' => [
                [
                    'width' => 100,
                    'height' => 100,
                    'length' => 100,
                    'maxWeight' => 200,
                ],
                [
                    'width' => 25,
                    'height' => 20,
                    'length' => 15,
                    'maxWeight' => 120,
                ],
                [
                    'width' => 50,
                    'height' => 30,
                    'length' => 20,
                    'maxWeight' => 150,
                ],
            ],
            'products' => [
                [
                    'id' => 1,
                    'width' => 10,
                    'height' => 10,
                    'length' => 10,
                    'weight' => 50,
                ],
                [
                    'id' => 2,
                    'width' => 10,
                    'height' => 10,
                    'length' => 5,
                    'weight' => 20,
                ],
            ],
            'expected' => 1,
        ];
    }

    public static function packDataProviderUnhappyPaths(): \Generator
    {
        yield 'no suitable box due to width' => [
            'boxes' => [
                [
                    'width' => 20,
                    'height' => 50,
                    'length' => 50,
                    'maxWeight' => 200,
                ],
            ],
            'products' => [
                [
                    'id' => 1,
                    'width' => 15,
                    'height' => 15,
                    'length' => 15,
                    'weight' => 45,
                ],
                [
                    'id' => 2,
                    'width' => 10,
                    'height' => 5,
                    'length' => 12,
                    'weight' => 55,
                ],
            ],
            'expected' => new SuitablePackageNotFoundException(),
        ];

        yield 'no suitable box due to weight' => [
            'boxes' => [
                [
                    'width' => 30,
                    'height' => 30,
                    'length' => 50,
                    'maxWeight' => 90,
                ],
            ],
            'products' => [
                [
                    'id' => 1,
                    'width' => 15,
                    'height' => 15,
                    'length' => 15,
                    'weight' => 45,
                ],
                [
                    'id' => 2,
                    'width' => 10,
                    'height' => 5,
                    'length' => 12,
                    'weight' => 55,
                ],
            ],
            'expected' => new SuitablePackageNotFoundException(),
        ];

        yield 'no boxes available' => [
            'boxes' => [],
            'products' => [
                [
                    'id' => 1,
                    'width' => 10,
                    'height' => 10,
                    'length' => 10,
                    'weight' => 5,
                ],
            ],
            'expected' => new SuitablePackageNotFoundException('No box available'),
        ];
    }

    private function createPackRequest(array $productData): PackRequest
    {
        $products = [];
        foreach ($productData as $data) {
            $products[] = new ProductDto($data['id'], $data['width'], $data['height'], $data['length'], $data['weight']);
        }

        return new PackRequest($products);
    }
}
