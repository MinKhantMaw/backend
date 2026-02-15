<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Stripe-Signature');
        $secret = (string) config('services.stripe.webhook_secret');

        if ($header === null || $secret === '') {
            return ApiResponse::error('Invalid webhook signature.', 400);
        }

        // Placeholder verifier. Replace with Stripe SDK verifier in production.
        if (! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $header)) {
            return ApiResponse::error('Invalid webhook signature.', 400);
        }

        return $next($request);
    }
}
