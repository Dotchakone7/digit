<?php

namespace Tests\Feature\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentConfirmedNotification;
use App\Payments\Gateways\SandboxGateway;
use App\Payments\PaymentManager;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private function orderWith(string $gateway): Order
    {
        $this->user = $this->customer();
        $this->actingAs($this->user);
        $this->addToCart($this->product(['price' => 20000, 'stock' => 5]));
        $this->post(route('checkout.store'), $this->checkoutPayload($this->shippingMethod(['price' => 0]), ['payment_method' => $gateway]));

        return Order::query()->sole();
    }

    private function webhook(Payment $payment, string $status, ?int $amount = null, ?string $signature = null)
    {
        $body = json_encode(['reference' => $payment->reference, 'status' => $status, 'amount' => $amount ?? $payment->amount]);
        $signature ??= app(PaymentManager::class)->gateway('sandbox')->sign($body);

        return $this->call('POST', route('payments.webhook', 'sandbox'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SANDBOX_SIGNATURE' => $signature,
        ], $body);
    }

    public function test_online_checkout_redirects_to_the_provider_page(): void
    {
        $this->user = $this->customer();
        $this->actingAs($this->user);
        $this->addToCart($this->product());

        $response = $this->post(route('checkout.store'), $this->checkoutPayload($this->shippingMethod(), ['payment_method' => 'sandbox']));

        $payment = Payment::query()->sole();
        $response->assertRedirect(route('payments.sandbox.show', $payment));
        $this->assertNotNull($payment->expires_at);
    }

    public function test_a_signed_webhook_confirms_the_payment_and_the_order(): void
    {
        Notification::fake();
        $order = $this->orderWith('sandbox');
        $payment = $order->payments()->sole();

        $this->webhook($payment, 'paid')->assertOk()->assertJson(['received' => true, 'status' => 'paid']);

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
        Notification::assertSentTo($this->user, PaymentConfirmedNotification::class);
    }

    public function test_webhooks_with_an_invalid_signature_are_rejected(): void
    {
        $order = $this->orderWith('sandbox');
        $payment = $order->payments()->sole();

        $this->webhook($payment, 'paid', signature: 'forged')->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_an_amount_mismatch_never_confirms_the_payment(): void
    {
        $order = $this->orderWith('sandbox');
        $payment = $order->payments()->sole();

        $this->webhook($payment, 'paid', amount: 100)->assertOk();

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_confirmation_is_idempotent(): void
    {
        $order = $this->orderWith('sandbox');
        $payment = $order->payments()->sole();

        $this->webhook($payment, 'paid');
        $this->webhook($payment, 'paid');
        app(PaymentService::class)->markPaid($payment->fresh());

        $this->assertSame(2, $order->statusHistories()->count(), 'Created + confirmed, only once.');
    }

    public function test_coming_back_to_the_return_url_does_not_mark_the_payment_as_paid(): void
    {
        $order = $this->orderWith('sandbox');
        $payment = $order->payments()->sole();

        $this->get(route('payments.return', $payment))->assertOk()->assertSee('Vérification du paiement');
        $this->get(route('payments.return', $payment).'?status=paid&success=1')->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_the_sandbox_simulator_goes_through_the_signed_webhook_pipeline(): void
    {
        $order = $this->orderWith('sandbox');
        $payment = $order->payments()->sole();

        $this->post(route('payments.sandbox.complete', $payment), ['outcome' => 'paid'])->assertRedirect(route('payments.return', $payment));

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
    }

    public function test_the_sandbox_is_never_available_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->assertFalse(app(PaymentManager::class)->isAvailable('sandbox'));
        $this->assertFalse((new SandboxGateway('sandbox', ['secret' => 'x']))->isAvailable());
    }

    public function test_a_failed_payment_can_be_retried_with_another_method(): void
    {
        $order = $this->orderWith('sandbox');
        $this->webhook($order->payments()->sole(), 'failed');
        $this->assertTrue($order->fresh()->awaitsPayment());

        $this->post(route('payments.store', $order), ['payment_method' => 'cash_on_delivery'])
            ->assertRedirect(route('checkout.confirmation', $order));

        $this->assertSame('cash_on_delivery', $order->fresh()->payment_method);
        $this->assertSame(2, $order->payments()->count());
    }

    public function test_expired_payments_cancel_the_order_and_release_stock(): void
    {
        $order = $this->orderWith('sandbox');
        $product = $order->items()->sole()->product;
        $this->assertSame(4, $product->fresh()->stock);

        $this->travel(2)->hours();
        $this->artisan('payments:expire')->assertSuccessful();

        $this->assertSame(PaymentStatus::Expired, $order->payments()->sole()->status);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_manual_mobile_money_requires_staff_verification(): void
    {
        $order = $this->orderWith('manual_mobile_money');
        $payment = $order->payments()->sole();

        $this->post(route('payments.transfer', $payment), ['operator' => 'orange', 'transaction_id' => 'MP2410.1234.A1', 'payer_phone' => '+225 07 12 34 56 78'])
            ->assertRedirect(route('checkout.confirmation', $order));

        $payment->refresh();
        $this->assertSame(PaymentStatus::Processing, $payment->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertStringEndsWith('5678', $payment->meta['payer_phone']);
        $this->assertStringNotContainsString('0712', $payment->meta['payer_phone'], 'The full payer number is not stored.');

        $this->actingAs($this->customer())->post(route('admin.payments.confirm', $payment))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.payments.confirm', $payment))->assertRedirect();
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
    }
}
