<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application;
use App\Application\Dto\PackageOutputDto;
use App\Application\Dto\ProductDto;
use App\Application\Formatter\ErrorResponseFormatter;
use App\Domain\Packing\PackingCalculatorInterface;
use App\Infrastructure\Bin3DPacking\Request\PackRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApplicationTest extends TestCase
{
    private MockObject $serializer;

    private MockObject $validator;

    private MockObject $packingCalculator;

    private MockObject $errorResponseFormatter;

    private MockObject $logger;

    private Application $application;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->packingCalculator = $this->createMock(PackingCalculatorInterface::class);
        $this->errorResponseFormatter = $this->createMock(ErrorResponseFormatter::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->application = new Application(
            $this->serializer,
            $this->validator,
            $this->packingCalculator,
            $this->errorResponseFormatter,
            $this->logger
        );
    }

    public function testRunWithValidData(): void
    {
        $requestContent = (string) json_encode([
            'products' => [
                [
                    'id' => 1,
                    'width' => 15,
                    'height' => 15,
                    'length' => 15,
                    'weight' => 45,
                ],
            ],
        ]);

        $request = Request::create('pack', 'POST', content: $requestContent);

        $packRequest = new PackRequest([
            new ProductDto(1, 15, 15, 15, 45),
        ]);

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($requestContent, PackRequest::class, 'json')
            ->willReturn($packRequest);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($packRequest)
            ->willReturn(new ConstraintViolationList());

        $this->packingCalculator->expects($this->once())
            ->method('calculate')
            ->with($packRequest->products)
            ->willReturn(new PackageOutputDto(20, 20, 20, 50));

        $this->logger->expects($this->never())
            ->method('info');

        $response = $this->application->run($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJsonStringEqualsJsonString(
            (string) json_encode([
                'width' => 20,
                'height' => 20,
                'length' => 20,
                'weight' => 50,
            ]),
            (string) $response->getContent()
        );
    }

    public function testRunWithValidationErrors(): void
    {
        $requestContent = (string) json_encode([
            'products' => [
                [
                    'id' => 230,
                    'weight' => -1,
                ],
            ],
        ]);

        $request = Request::create('pack', 'POST', content: $requestContent);

        $packRequest = new PackRequest([
            new ProductDto(230, 15, 20, 35, -45),
        ]);

        $constraintViolation = new ConstraintViolation(
            'This value should be positive.',
            null,
            [],
            '',
            'weight',
            -45
        );

        $constraintViolationList = new ConstraintViolationList([$constraintViolation]);

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($requestContent, PackRequest::class, 'json')
            ->willReturn($packRequest);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($packRequest)
            ->willReturn($constraintViolationList);

        $this->errorResponseFormatter->expects($this->once())
            ->method('format')
            ->with($constraintViolationList)
            ->willReturn(new JsonResponse([
                'errors' => [
                    'property' => 'products[0].weight',
                    'message' => 'This value should be positive.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY));

        $this->packingCalculator->expects($this->never())
            ->method('calculate');

        $response = $this->application->run($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame([
            'errors' => [
                'property' => 'products[0].weight',
                'message' => 'This value should be positive.',
            ],
        ], json_decode((string) $response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }
}
