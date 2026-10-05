<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ReviewStatus;
use App\Enums\RoleSlug;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Events\PaymentConfirmed;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Orders\CheckoutData;
use App\Services\Orders\OrderService;
use App\Services\OrderStatusService;
use App\Services\PaymentService;
use App\Services\SettingsService;
use App\Support\Media;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Realistic demonstration data. Orders are created through the real
 * services (stock, totals, history, payments) at past dates.
 */
class DemoSeeder extends Seeder
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly OrderStatusService $statuses,
        private readonly PaymentService $payments,
    ) {}

    private Carbon $origin;

    public function run(): void
    {
        $this->origin = Carbon::now();
        if (app()->isProduction()) {
            throw new RuntimeException('Les données de démonstration ne peuvent pas être chargées en production.');
        }

        mt_srand(2026);
        fake()->seed(2026);
        Event::fake([OrderPlaced::class, PaymentConfirmed::class, OrderStatusChanged::class]);

        $this->call([RoleSeeder::class, ShippingMethodSeeder::class]);

        $this->seedStaff();
        $this->seedCatalog();
        $this->seedCoupons();
        $customers = $this->seedCustomers();
        $this->seedOrders($customers);
        $this->seedReviews();
        $this->seedSettings();

        Carbon::setTestNow();
    }

    private function seedStaff(): void
    {
        foreach ([
            [RoleSlug::SuperAdmin, 'Super Admin', 'superadmin@example.com'],
            [RoleSlug::Admin, 'Awa Koné', 'admin@example.com'],
            [RoleSlug::Manager, 'Yao Kouassi', 'gestionnaire@example.com'],
        ] as [$role, $name, $email]) {
            $user = User::query()->firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'password' => 'password',
                'role_id' => UserFactory::roleId($role),
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();
        }

        $this->command?->info('Comptes démo : superadmin@ / admin@ / gestionnaire@example.com — mot de passe « password ».');
    }

    private function seedCatalog(): void
    {
        $disk = Storage::disk(Media::disk());
        $copy = function (string $image) use ($disk): string {
            $path = "demo/{$image}.webp";
            if (! $disk->exists($path)) {
                $disk->put($path, file_get_contents(__DIR__."/demo-images/{$image}.webp"), 'public');
            }

            return $path;
        };

        $categories = [];
        foreach (DemoCatalog::categories() as $position => $data) {
            $parent = Category::query()->updateOrCreate(['slug' => $data['slug']], [
                'name' => $data['name'], 'description' => $data['description'],
                'image_path' => $copy($data['image']), 'is_active' => true, 'position' => $position,
            ]);
            $categories[$data['slug']] = $parent;

            foreach ($data['children'] ?? [] as $childPosition => $child) {
                $categories[$child['slug']] = Category::query()->updateOrCreate(['slug' => $child['slug']], [
                    'parent_id' => $parent->id, 'name' => $child['name'], 'is_active' => true, 'position' => $childPosition,
                ]);
            }
        }

        foreach (DemoCatalog::products() as $index => $row) {
            [$categorySlug, $name, $price, $salePrice, $images, $stock, $featured, $short, $specs] = $row;
            $variantGroups = $row[9] ?? [];
            $slug = Str::slug($name);

            $product = Product::query()->updateOrCreate(['slug' => $slug], [
                'category_id' => $categories[$categorySlug]->id,
                'name' => $name,
                'sku' => 'DG-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'short_description' => $short,
                'description' => $short."\n\n".$this->longDescription($name),
                'price' => $price,
                'sale_price' => $salePrice,
                'sale_ends_at' => $salePrice && $index % 3 === 0 ? now()->addDays(10 + $index) : null,
                'stock' => $stock,
                'status' => ProductStatus::Published,
                'is_featured' => $featured,
                'specifications' => collect($specs)->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values()->all(),
                'published_at' => now()->subDays(65 - $index * 2),
            ]);

            $product->images()->delete();
            foreach ($images as $position => $image) {
                $product->images()->create(['path' => $copy($image), 'alt' => $name, 'position' => $position, 'is_primary' => $position === 0]);
            }

            if ($variantGroups) {
                $product->variants()->delete();
                foreach ($variantGroups as $option => $values) {
                    $position = 0;
                    foreach ($values as $value => $variantStock) {
                        $product->variants()->create([
                            'name' => "{$option} {$value}",
                            'sku' => $product->sku.'-'.Str::upper(Str::slug((string) $value)),
                            'options' => [$option => (string) $value],
                            'stock' => $variantStock,
                            'is_active' => true,
                            'position' => $position++,
                        ]);
                    }
                }
                $product->update(['stock' => $product->variants()->sum('stock')]);
            }
        }
    }

    private function seedCoupons(): void
    {
        Coupon::query()->updateOrCreate(['code' => 'BIENVENUE10'], [
            'description' => '10 % sur la première commande', 'type' => CouponType::Percent, 'value' => 10,
            'max_discount_amount' => 15000, 'usage_limit_per_user' => 1, 'is_active' => true,
        ]);
        Coupon::query()->updateOrCreate(['code' => 'PROMO5000'], [
            'description' => '5 000 FCFA dès 40 000 FCFA d’achat', 'type' => CouponType::Fixed, 'value' => 5000,
            'min_order_amount' => 40000, 'usage_limit' => 200, 'is_active' => true,
            'starts_at' => now()->subDays(10), 'ends_at' => now()->addMonths(2),
        ]);
    }

    /** @return list<User> */
    private function seedCustomers(): array
    {
        $names = ['Aya Traoré', 'Kouadio Yao', 'Fatou Diallo', 'Mariam Bamba', 'Serge N’Guessan', 'Adjoua Konan',
            'Ibrahim Ouattara', 'Christelle Aké', 'Moussa Coulibaly', 'Nadège Kouamé', 'Jean-Marc Tanoh', 'Salimata Cissé'];
        $districts = ['Cocody', 'Plateau', 'Marcory', 'Yopougon', 'Treichville', 'Riviera 2', 'Angré', 'Koumassi'];

        return collect($names)->map(function (string $name, int $i) use ($districts) {
            $email = Str::slug(Str::ascii($name), '.').'@example.com';
            $user = User::query()->where('email', $email)->first() ?? User::factory()->create([
                'name' => $name, 'email' => $email, 'created_at' => now()->subDays(90 - $i * 5),
            ]);

            if (! $user->addresses()->exists()) {
                $user->addresses()->create([
                    'label' => 'Maison', 'full_name' => $name, 'phone' => $user->phone, 'city' => 'Abidjan',
                    'district' => $districts[$i % count($districts)], 'street' => 'Rue '.(10 + $i * 7).', villa '.(3 + $i),
                    'landmark' => 'Près de la pharmacie du quartier', 'is_default' => true,
                ]);
            }

            return $user;
        })->all();
    }

    private function seedOrders(array $customers): void
    {
        if (Order::query()->exists()) {
            return;
        }

        $shipping = ShippingMethod::query()->active()->get();
        $outcomes = [OrderStatus::Delivered, OrderStatus::Delivered, OrderStatus::Delivered, OrderStatus::Delivered,
            OrderStatus::Delivered, OrderStatus::Shipped, OrderStatus::Processing, OrderStatus::Confirmed,
            OrderStatus::Pending, OrderStatus::Cancelled];

        $origin = $this->origin;
        $day = 58;
        foreach (range(1, 34) as $n) {
            $user = $customers[$n % count($customers)];
            $placedAt = $origin->copy()->subDays(max(0, $day))->setTime(mt_rand(8, 20), mt_rand(0, 59))->min($origin)->copy();
            $day -= mt_rand(1, 3);
            Carbon::setTestNow($placedAt);

            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
            $cart->items()->delete();
            $products = Product::query()->with('activeVariants')->get();
            $available = $products->filter(fn (Product $p) => $p->published_at->lte($placedAt));
            foreach ($available->random(min(mt_rand(1, 3), $available->count())) as $product) {
                $variant = $product->activeVariants->where('stock', '>', 1)->first();
                $stock = $variant?->stock ?? $product->stock;
                if ($stock < 2 || ($product->activeVariants->isNotEmpty() && ! $variant)) {
                    continue;
                }
                $cart->items()->create(['product_id' => $product->id, 'product_variant_id' => $variant?->id, 'quantity' => mt_rand(1, 2)]);
            }
            if (! $cart->items()->exists()) {
                continue;
            }

            /** @var Address $address */
            $address = $user->addresses()->first();
            $method = $shipping->random();
            $gateway = $n % 3 === 0 ? 'manual_mobile_money' : 'cash_on_delivery';

            $order = $this->orders->placeOrder($user, $cart, new CheckoutData(
                customerName: $user->name, customerEmail: $user->email, customerPhone: $user->phone,
                address: $address->toSnapshot(), shippingMethod: $method, paymentMethod: $gateway,
            ));

            $this->progress($order, $gateway, $outcomes[$n % count($outcomes)], $placedAt);
        }

        Carbon::setTestNow();
    }

    private function progress(Order $order, string $gateway, OrderStatus $target, Carbon $placedAt): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();
        $payment = $order->payments()->make([
            'gateway' => $gateway, 'reference' => 'PAY-'.Str::upper(Str::random(12)), 'amount' => $order->total,
            'currency' => $order->currency,
            'meta' => $gateway === 'manual_mobile_money' ? ['operator' => collect(['orange', 'mtn', 'moov', 'wave'])->random(), 'transaction_id' => 'TX'.mt_rand(10000000, 99999999)] : null,
        ]);
        $payment->status = PaymentStatus::Pending;
        $payment->save();

        if ($target === OrderStatus::Pending) {
            return;
        }

        if ($target === OrderStatus::Cancelled) {
            Carbon::setTestNow($placedAt->copy()->addHours(5)->min($this->origin)->copy());
            $this->statuses->transition($order, OrderStatus::Cancelled, $admin, 'Client injoignable');

            return;
        }

        $clock = $placedAt->copy()->addHours(2)->min($this->origin)->copy();
        Carbon::setTestNow($clock);
        if ($gateway === 'manual_mobile_money') {
            $this->payments->markPaid($payment, $admin); // pending → confirmed
        } else {
            $this->statuses->transition($order, OrderStatus::Confirmed, $admin, 'Commande confirmée par téléphone');
        }

        foreach ([OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered] as $step) {
            if ($order->refresh()->status === $target) {
                break;
            }
            $clock->addHours(mt_rand(6, 26));
            if ($clock->gt($this->origin)) {
                break; // Never simulate events in the future: recent orders stay in progress.
            }
            Carbon::setTestNow($clock);
            if ($step === OrderStatus::Shipped) {
                $order->shipments()->create(['carrier' => 'Livreur partenaire', 'status' => 'pending']);
            }
            $this->statuses->transition($order, $step, $admin);
            if ($step === OrderStatus::Delivered && $gateway === 'cash_on_delivery') {
                $this->payments->markPaid($payment->refresh(), $admin);
            }
        }
    }

    private function seedReviews(): void
    {
        $comments = DemoCatalog::reviewComments();
        $i = 0;

        Order::query()->where('status', OrderStatus::Delivered)->with('items')->get()->each(function (Order $order) use ($comments, &$i) {
            foreach ($order->items as $item) {
                if (! $item->product_id || $i++ % 4 === 3 || Review::query()->where(['product_id' => $item->product_id, 'user_id' => $order->user_id])->exists()) {
                    continue;
                }
                [$rating, $title, $comment] = $comments[$i % count($comments)];
                $review = new Review(['product_id' => $item->product_id, 'user_id' => $order->user_id, 'order_id' => $order->id,
                    'rating' => $rating, 'title' => $title, 'comment' => $comment]);
                $review->status = $i % 6 === 0 ? ReviewStatus::Pending : ReviewStatus::Approved;
                $review->moderated_at = $review->status === ReviewStatus::Approved ? $order->delivered_at : null;
                $review->created_at = $order->delivered_at?->copy()->addDays(2);
                $review->save();
            }
        });

        Product::query()->each(fn (Product $product) => $product->refreshRating());
    }

    private function seedSettings(): void
    {
        app(SettingsService::class)->set([
            'announcement' => 'Livraison offerte dès 50 000 FCFA à Abidjan · Paiement Mobile Money ou à la livraison',
            'hero_eyebrow' => 'Nouvelle collection disponible',
            'about_text' => 'Une boutique en ligne exigeante : produits sélectionnés, prix justes, livraison rapide et service client à l’écoute.',
        ]);
    }

    private function longDescription(string $name): string
    {
        return "Nous avons sélectionné {$name} pour sa qualité de fabrication et sa fiabilité au quotidien. Chaque article est contrôlé avant expédition et soigneusement emballé.\n\nBesoin d’un conseil ? Notre service client vous répond avant et après votre achat.";
    }
}
