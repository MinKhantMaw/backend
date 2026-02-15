<?php

namespace App\Services\Ecommerce;

use App\Jobs\Ecommerce\HandleOrderPaidJob;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Payment;
use App\Models\Ecommerce\WebhookEvent;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function createPaymentIntent(Order $order): array
    {
        // Replace with Stripe SDK call in production.
        $id = 'pi_'.str()->random(24);

        return [
            'provider' => 'stripe',
            'payment_intent_id' => $id,
            'client_secret' => $id.'_secret_'.str()->random(24),
        ];
    }

    public function processWebhook(array $event): void
    {
        DB::transaction(function () use ($event) {
            $alreadyProcessed = WebhookEvent::query()->where('event_id', $event['id'])->exists();

            if ($alreadyProcessed) {
                return;
            }

            WebhookEvent::query()->create([
                'provider' => 'stripe',
                'event_id' => $event['id'],
                'event_type' => $event['type'],
                'payload' => $event,
                'processed_at' => now(),
            ]);

            if ($event['type'] !== 'payment_intent.succeeded') {
                return;
            }

            $intentId = $event['data']['object']['id'] ?? null;
            $payment = Payment::query()->where('payment_intent_id', $intentId)->lockForUpdate()->first();

            if (! $payment || $payment->status === 'succeeded') {
                return;
            }

            $payment->update([
                'status' => 'succeeded',
                'paid_at' => now(),
                'provider_payload' => $event,
            ]);

            $payment->order()->update([
                'status' => 'paid',
                'placed_at' => now(),
            ]);

            HandleOrderPaidJob::dispatch($payment->order_id);
        });
    }
}
