<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Packing;

use App\Application\Dto\PackageOutputDto;
use App\Application\Dto\ProductDto;
use App\Application\Packing\ChainedStrategiesPackingCalculator;
use App\Domain\Entity\Packaging;
use App\Domain\Exception\MultipleBinsNotSupportedException;
use App\Domain\Exception\PackingException;
use App\Domain\Exception\PackingStrategyNotApplicableException;
use App\Domain\Exception\SuitablePackageNotFoundException;
use App\Domain\Strategy\PackingStrategyInterface;
use PHPUnit\Framework\TestCase;

class ChainedStrategiesPackingCalculatorTest extends TestCase
{
    public function testSuccessfulStrategyReturnsExpectedPackage(): void
    {
        $product = $this->createMock(ProductDto::class);
        $productsToPack = [$product];
        $expectedPackaging = $this->createMock(Packaging::class);

        $workingStrategy = $this->createMock(PackingStrategyInterface::class);
        $workingStrategy->expects($this->once())
            ->method('pack')
            ->with($productsToPack)
            ->willReturn($expectedPackaging);

        $calculator = new ChainedStrategiesPackingCalculator([$workingStrategy]);
        $result = $calculator->calculate($productsToPack);

        $this->assertEquals(PackageOutputDto::createFromPacking($expectedPackaging), $result);
    }

    public function testChainSkipsFailingStrategiesAndUsesFirstSuccess(): void
    {
        $product = $this->createMock(ProductDto::class);
        $productsToPack = [$product];
        $finalPackaging = $this->createMock(Packaging::class);

        $failingStrategy = $this->createMock(PackingStrategyInterface::class);
        $failingStrategy->expects($this->once())
            ->method('pack')
            ->with($productsToPack)
            ->willThrowException(new PackingException('Packing failed'));

        $successfulStrategy = $this->createMock(PackingStrategyInterface::class);
        $successfulStrategy->expects($this->once())
            ->method('pack')
            ->with($productsToPack)
            ->willReturn($finalPackaging);

        $calculator = new ChainedStrategiesPackingCalculator([$failingStrategy, $successfulStrategy]);
        $result = $calculator->calculate($productsToPack);

        $this->assertEquals(PackageOutputDto::createFromPacking($finalPackaging), $result);
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

        $calculator = new ChainedStrategiesPackingCalculator([$firstStrategy, $secondStrategy]);
        $calculator->calculate($productsToPack);
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

        $calculator = new ChainedStrategiesPackingCalculator([$strategy]);
        $calculator->calculate($productsToPack);
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

        $calculator = new ChainedStrategiesPackingCalculator([$strategy]);
        $calculator->calculate($productsToPack);
    }
}
