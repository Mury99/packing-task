<?php

declare(strict_types=1);

namespace App\Application\Formatter;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ErrorResponseFormatter
{
    private const string SEPARATOR = ', ';

    public function formatDenormalizationErrors(PartialDenormalizationException $e): Response
    {
        $violations = new ConstraintViolationList();

        foreach ($e->getErrors() as $exception) {
            $message = sprintf(
                'The type must be one of "%s" ("%s" given).',
                implode(self::SEPARATOR, $exception->getExpectedTypes()),
                $exception->getCurrentType()
            );

            $parameters = [];

            if ($exception->canUseMessageForUser()) {
                $parameters['hint'] = $exception->getMessage();
            }

            $violations->add(new ConstraintViolation(
                $message,
                '',
                $parameters,
                null,
                $exception->getPath(),
                null
            ));
        }

        return $this->format($violations);
    }

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
