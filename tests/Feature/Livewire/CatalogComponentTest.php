<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Shop\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_update_results_and_ignore_invalid_values(): void
    {
        $this->product(['name' => 'Baskets urbaines', 'price' => 30000]);
        $this->product(['name' => 'Sac en cuir', 'price' => 5000]);

        Livewire::test(Catalog::class)
            ->assertSee('Baskets urbaines')->assertSee('Sac en cuir')
            ->set('q', 'baskets')
            ->assertSee('Baskets urbaines')->assertDontSee('Sac en cuir')
            ->call('resetFilters')
            ->set('maxPrice', '10000')
            ->assertSee('Sac en cuir')->assertDontSee('Baskets urbaines')
            ->set('sort', 'not-a-sort')
            ->assertOk();
    }

    public function test_filters_are_read_from_the_url(): void
    {
        $this->product(['name' => 'Baskets urbaines']);
        $this->product(['name' => 'Sac en cuir']);

        Livewire::withQueryParams(['q' => 'sac'])->test(Catalog::class)
            ->assertSet('q', 'sac')
            ->assertSee('Sac en cuir')->assertDontSee('Baskets urbaines');
    }
}
