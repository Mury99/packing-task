<?php

declare(strict_types=1);

namespace App;

use App\Dto\PackRequest;
use App\Exception\MultipleBinsNotSupportedException;
use App\Exception\PackingStrategyNotApplicableException;
use App\Exception\SuitablePackageNotFoundException;
use App\Formatter\ErrorResponseFormatter;
use App\Service\PackingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class Application
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private PackingService $packingService,
        private ErrorResponseFormatter $errorResponseFormatter,
        private LoggerInterface $logger,
    ) {
    }

    public function run(Request $request): Response
    {
        $packRequest = $this->serializer->deserialize($request->getContent(), PackRequest::class, 'json');
        $errors = $this->validator->validate($packRequest);

        if (count($errors) > 0) {
            return $this->errorResponseFormatter->format($errors);
        }

        try {
            return new JsonResponse(
                $this->packingService->calculateBox($packRequest->products)
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
