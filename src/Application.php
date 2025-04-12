<?php

declare(strict_types=1);

namespace App;

use App\Application\Formatter\ErrorResponseFormatter;
use App\Domain\Exception\MultipleBinsNotSupportedException;
use App\Domain\Exception\PackingStrategyNotApplicableException;
use App\Domain\Exception\SuitablePackageNotFoundException;
use App\Domain\Packing\PackingCalculatorInterface;
use App\Infrastructure\Bin3DPacking\Request\PackRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class Application
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private PackingCalculatorInterface $packingCalculator,
        private ErrorResponseFormatter $errorResponseFormatter,
        private LoggerInterface $logger,
    ) {
    }

    public function run(Request $request): Response
    {
        try {
            $packRequest = $this->serializer->deserialize($request->getContent(), PackRequest::class, 'json', [
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
            ]);
        } catch (PartialDenormalizationException $e) {
            return $this->errorResponseFormatter->formatDenormalizationErrors($e);
        }

        $errors = $this->validator->validate($packRequest);
        if (count($errors) > 0) {
            return $this->errorResponseFormatter->format($errors);
        }

        try {
            return new JsonResponse(
                $this->packingCalculator->calculate($packRequest->products)
            );
        } catch (MultipleBinsNotSupportedException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'code' => 'multiple-bins-not-supported',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (SuitablePackageNotFoundException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'code' => 'suitable-package-not-found',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (PackingStrategyNotApplicableException) {
            return new JsonResponse([
                'error' => 'We are unable to pack the provided products at this time. Please check the product specifications or try again later',
                'code' => 'packing-not-applicable',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Unexpected error: %s', $e->getMessage()));

            return new JsonResponse([
                'error' => 'An unexpected error occurred. Please try again later',
                'code' => 'unexpected-error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
