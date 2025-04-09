<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain;

use App\Chain\PackingStrategyChain;
use App\Dto\PackageOutputDto;
use App\Dto\ProductDto;
use App\Exception\MultipleBinsNotSupportedException;
use App\Exception\PackingException;
use App\Exception\PackingStrategyNotApplicableException;
use App\Exception\SuitablePackageNotFoundException;
use App\Strategy\PackingStrategyInterface;
use PHPUnit\Framework\TestCase;

class PackingStrategyChainTest extends TestCase
{
    public function testSuccessfulStrategyReturnsExpectedPackage(): void
    {
        $product = $this->createMock(ProductDto::class);
        $productsToPack = [$product];
        $expectedPackage = $this->createMock(PackageOutputDto::class);

        $workingStrategy = $this->createMock(PackingStrategyInterface::class);
        $workingStrategy->expects($this->once())
            ->method('pack')
            ->with($productsToPack)
            ->willReturn($expectedPackage);

        $strategyChain = new PackingStrategyChain([$workingStrategy]);
        $result = $strategyChain->chain($productsToPack);

        $this->assertSame($expectedPackage, $result, 'The chain should return the package from a successful strategy.');
    }

    public function testChainSkipsFailingStrategiesAndUsesFirstSuccess(): void
    {
        $product = $this->createMock(ProductDto::class);
        $productsToPack = [$product];
        $finalPackage = $this->createMock(PackageOutputDto::class);

        $failingStrategy = $this->createMock(PackingStrategyInterface::class);
        $failingStrategy->expects($this->once())
            ->method('pack')
            ->with($productsToPack)
            ->willThrowException(new PackingException('Packing failed'));

        $successfulStrategy = $this->createMock(PackingStrategyInterface::class);
        $successfulStrategy->expects($this->once())
            ->method('pack')
            ->with($productsToPack)
            ->willReturn($finalPackage);

        $strategyChain = new PackingStrategyChain([$failingStrategy, $successfulStrategy]);
        $result = $strategyChain->chain($productsToPack);

        $this->assertSame($finalPackage, $result);
    }

    public function testThrowsExceptionIfAllStrategiesFail(): void
    {
        $this->expectException(PackingStrategyNotApplicableException::class);

        $product = $this->createMock(ProductDto::class);
        $productsToPack = [$product];

        $firstStrategy = $this->createMock(PackingStrategyInterface::class);
        $firstStrategy->expects($this->once())
            ->method('pack')
            ->willThrowException(new PackingException('First strategy failed'));

        $secondStrategy = $this->createMock(PackingStrategyInterface::class);
        $secondStrategy->expects($this->once())
            ->method('pack')
            ->willThrowException(new PackingException('Second strategy failed'));

        $strategyChain = new PackingStrategyChain([$firstStrategy, $secondStrategy]);
        $strategyChain->chain($productsToPack);
    }

    public function testPropagatesMultipleBinsNotSupportedException(): void
    {
        $this->expectException(MultipleBinsNotSupportedException::class);

        $product = $this->createMock(ProductDto::class);
        $productsToPack = [$product];

        $strategy = $this->createMock(PackingStrategyInterface::class);
        $strategy->expects($this->once())
            ->method('pack')
            ->with($productsToPack)
            ->willThrowException(new MultipleBinsNotSupportedException());

        $strategyChain = new PackingStrategyChain([$strategy]);
        $strategyChain->chain($productsToPack);
    }

    public function testPropagatesSuitablePackageNotFoundException(): void
    {
        $this->expectException(SuitablePackageNotFoundException::class);
        $this->expectExceptionMessage('No suitable box found for the given products');

        $product = $this->createMock(ProductDto::class);
        $productsToPack = [$product];

        $strategy = $this->createMock(PackingStrategyInterface::class);
        $strategy->expects($this->once())
            ->method('pack')
            ->with($productsToPack)
            ->willThrowException(new SuitablePackageNotFoundException());

        $strategyChain = new PackingStrategyChain([$strategy]);
        $strategyChain->chain($productsToPack);
    }
}
