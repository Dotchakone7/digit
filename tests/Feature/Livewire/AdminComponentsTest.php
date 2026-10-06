<?php

namespace Tests\Feature\Livewire;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\OrderManager;
use App\Livewire\Admin\OrderTable;
use App\Livewire\Admin\PaymentTable;
use App\Livewire\Admin\ProductTable;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Livewire actions are authorized server-side on every request, like controllers. */
class AdminComponentsTest extends TestCase
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

    public function test_customers_and_guests_cannot_use_admin_components(): void
    {
        Livewire::test(ProductTable::class)->assertForbidden();

        $this->actingAs($this->customer());
        Livewire::test(ProductTable::class)->assertForbidden();
        Livewire::test(OrderTable::class)->assertForbidden();
        Livewire::test(Dashboard::class)->assertForbidden();
    }

    public function test_managers_cannot_open_the_payments_table_nor_confirm_a_payment(): void
    {
        $order = $this->placeOrder();
        $this->actingAs(User::factory()->manager()->create());

        Livewire::test(PaymentTable::class)->assertForbidden();
        Livewire::test(OrderManager::class, ['order' => $order])
            ->assertOk()
            ->assertDontSee('Fonds reçus')
            ->call('confirmPayment', $order->payments()->sole()->id)
            ->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $order->payments()->sole()->status);
    }

    public function test_product_table_filters_and_updates_stock_inline(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $mug = $this->product(['name' => 'Mug céramique', 'stock' => 4]);
        $this->product(['name' => 'Gourde inox']);

        Livewire::test(ProductTable::class)
            ->set('search', 'mug')
            ->assertSee('Mug céramique')
            ->assertDontSee('Gourde inox')
            ->call('updateStock', $mug->id, '25')
            ->assertDispatched('toast')
            ->call('updateStock', $mug->id, '-3')
            ->call('setStatus', $mug->id, 'draft');

        $this->assertSame(25, $mug->fresh()->stock);
        $this->assertSame(ProductStatus::Draft, $mug->fresh()->status);
    }

    public function test_order_manager_moves_the_order_through_the_workflow(): void
    {
        $order = $this->placeOrder();
        $this->actingAs($admin = User::factory()->admin()->create());

        $component = Livewire::test(OrderManager::class, ['order' => $order])
            ->set('comment', 'Client joint par téléphone')
            ->call('changeStatus', 'confirmed')
            ->assertSet('comment', '')
            ->call('changeStatus', 'delivered') // not allowed from "confirmed"
            ->assertDispatched('toast', type: 'error');

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
        $this->assertSame('Client joint par téléphone', $order->statusHistories()->reorder()->latest('id')->first()->comment);
        $this->assertSame($admin->id, $order->statusHistories()->reorder()->latest('id')->first()->user_id);

        $component->set('carrier', '')->call('addShipment')->assertHasErrors('carrier')
            ->set('carrier', 'Yango')->set('trackingNumber', 'YG-1')->call('addShipment')->assertHasNoErrors();
        $this->assertSame('YG-1', $order->shipments()->sole()->tracking_number);

        $component->call('confirmPayment', $order->payments()->sole()->id);
        $this->assertSame(PaymentStatus::Paid, $order->payments()->sole()->status);
    }

    public function test_cancelling_from_the_order_manager_restores_stock(): void
    {
        $order = $this->placeOrder();
        $product = $order->items()->sole()->product;
        $this->actingAs(User::factory()->manager()->create());

        Livewire::test(OrderManager::class, ['order' => $order])->call('changeStatus', 'cancelled');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_order_table_announces_new_orders(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $table = Livewire::test(OrderTable::class)->assertNotDispatched('toast');

        $order = $this->placeOrder();
        $this->actingAs(User::factory()->admin()->create());

        $table->call('$refresh')->assertDispatched('toast', message: 'Nouvelle commande reçue !')->assertSee($order->number);
    }
}
