<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_customers_are_forbidden(): void
    {
        $this->actingAs($this->customer())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->customer())->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_admins_can_open_every_section(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['admin.dashboard', 'admin.orders.index', 'admin.products.index', 'admin.categories.index', 'admin.payments.index',
            'admin.reviews.index', 'admin.coupons.index', 'admin.returns.index', 'admin.users.index', 'admin.settings.edit',
            'admin.shipping-methods.index', 'admin.newsletter.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_managers_have_restricted_permissions(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.orders.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.coupons.index'))->assertForbidden();
    }

    public function test_an_admin_cannot_promote_anyone_to_super_admin_nor_edit_a_super_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = $this->customer();
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->patch(route('admin.users.update', $customer), ['role' => 'super-admin'])->assertSessionHasErrors('role');
        $this->assertFalse($customer->fresh()->isSuperAdmin());

        $this->actingAs($admin)->patch(route('admin.users.update', $super), ['is_active' => 0])->assertForbidden();
        $this->assertTrue($super->fresh()->is_active);
    }

    public function test_an_admin_cannot_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.users.update', $admin), ['is_active' => 0])->assertForbidden();
    }

    public function test_an_admin_can_deactivate_a_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = $this->customer();

        $this->actingAs($admin)->patch(route('admin.users.update', $customer), ['is_active' => 0])->assertRedirect();
        $this->assertFalse($customer->fresh()->is_active);
    }

    public function test_a_deactivated_staff_member_loses_admin_access(): void
    {
        $admin = User::factory()->admin()->inactive()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
