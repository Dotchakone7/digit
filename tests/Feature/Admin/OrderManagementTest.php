<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(): Order
    {
        $this->actingAs($customer = $this->customer());
        $this->addToCart($this->product(['stock' => 5]), 2);
        $this->post(route('checkout.store'), $this->checkoutPayload($this->shippingMethod()));
        auth()->logout();

        return Order::query()->where('user_id', $customer->id)->sole();
    }

    public function test_staff_can_move_an_order_through_the_workflow_with_history_and_notifications(): void
    {
        Notification::fake();
        $order = $this->placeOrder();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        foreach ([OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
            $this->post(route('admin.orders.status', $order), ['status' => $status->value, 'comment' => 'ok'])->assertRedirect();
            $this->assertSame($status, $order->fresh()->status);
        }

        $order->refresh();
        $this->assertNotNull($order->shipped_at);
        $this->assertNotNull($order->delivered_at);
        $this->assertSame(5, $order->statusHistories()->count());
        $this->assertSame($admin->id, $order->statusHistories()->reorder()->latest('id')->first()->user_id);
        Notification::assertSentTo($order->user, OrderStatusUpdatedNotification::class, fn ($n) => $n->status === OrderStatus::Shipped);
        Notification::assertSentTo($order->user, OrderStatusUpdatedNotification::class, fn ($n) => $n->status === OrderStatus::Delivered);
    }

    public function test_forbidden_transitions_are_rejected(): void
    {
        $order = $this->placeOrder();
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.orders.status', $order), ['status' => 'delivered'])->assertSessionHasErrors('status');
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_cancelling_from_the_back_office_restores_stock(): void
    {
        $order = $this->placeOrder();
        $product = $order->items()->sole()->product;
        $this->assertSame(3, $product->fresh()->stock);

        $this->actingAs(User::factory()->manager()->create())
            ->post(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertRedirect();

        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_customers_cannot_change_order_status(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($order->user)->post(route('admin.orders.status', $order), ['status' => 'confirmed'])->assertForbidden();
    }

    public function test_contact_courier_redirects_to_the_configured_platform(): void
    {
        $order = $this->placeOrder();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.orders.courier', $order))
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'delivery']));

        app(SettingsService::class)->set(['courier_url' => 'https://livraison.example.com/nouvelle-course', 'courier_name' => 'Partenaire']);

        $this->actingAs($admin)->get(route('admin.orders.courier', $order))
            ->assertRedirect('https://livraison.example.com/nouvelle-course');
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('Contacter un livreur');
    }

    public function test_the_courier_url_must_be_http(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update'), ['courier_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('courier_url');
    }

    public function test_orders_can_be_searched_and_exported(): void
    {
        $order = $this->placeOrder();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.orders.index', ['q' => strtolower($order->number)]))->assertOk()->assertSee($order->number);
        $this->actingAs($admin)->get(route('admin.orders.export'))->assertOk()->assertDownload();
    }
}
