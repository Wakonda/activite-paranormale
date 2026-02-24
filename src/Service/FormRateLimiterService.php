<?php
namespace App\Service;

use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class FormRateLimiterService
{
    public function __construct(
        private RateLimiterFactoryInterface $formSubmissionLimiter
    ) {}

    public function check(Request $request, string $formType): void
    {
        $limiter = $this->formSubmissionLimiter->create(
            $request->getClientIp() . '-' . $formType
        );

        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(null);
        }
    }
}