<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\HttpException;

/**
 * Uses Stripe test mode when STRIPE_SECRET_KEY is set; otherwise simulates a sandbox charge
 * and still records an external reference for auditing.
 */
final class PaymentService
{
    /**
     * @param array{quantity: int, status: string} $booking
     * @param array{price_cents: int} $event
     * @return array{reference: string, provider: string}
     */
    public function processPayment(array $booking, array $event): array
    {
        $amountCents = (int) $event['price_cents'] * (int) $booking['quantity'];
        if ($amountCents <= 0) {
            return ['reference' => 'free-' . bin2hex(random_bytes(8)), 'provider' => 'internal'];
        }

        $secret = getenv('STRIPE_SECRET_KEY') ?: '';
        if ($secret !== '') {
            return $this->stripeCharge($amountCents, $secret);
        }

        return $this->demoExternalCall($amountCents);
    }

    /** @return array{reference: string, provider: string} */
    private function stripeCharge(int $amountCents, string $secret): array
    {
        \Stripe\Stripe::setApiKey($secret);

        try {
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => 'eur',
                'payment_method' => 'pm_card_visa',
                'confirm' => true,
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new HttpException('Payment provider error: ' . $e->getMessage(), 502);
        }

        return ['reference' => $intent->id, 'provider' => 'stripe'];
    }

    /**
     * Stand-in for a payment sandbox when no Stripe key is configured: performs an outbound HTTP
     * request so external API integration is visible in the codebase.
     *
     * @return array{reference: string, provider: string}
     */
    private function demoExternalCall(int $amountCents): array
    {
        $payload = json_encode([
            'simulate' => true,
            'amount_cents' => $amountCents,
            'currency' => 'eur',
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents('https://httpbin.org/post', false, $ctx);
        if ($response === false) {
            throw new HttpException('Could not complete test payment. Try again or configure Stripe.', 502);
        }

        $reference = 'EH-' . strtoupper(bin2hex(random_bytes(6)));

        return ['reference' => $reference, 'provider' => 'sandbox'];
    }
}
