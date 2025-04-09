<?php

declare(strict_types=1);

namespace App\Formatter;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ErrorResponseFormatter
{
    public function format(ConstraintViolationListInterface $violationList): Response
    {
        $formattedErrors = array_map(
            fn (ConstraintViolationInterface $violation) => [
                'property' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ],
            iterator_to_array($violationList)
        );

        return new JsonResponse([
            'errors' => $formattedErrors,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
