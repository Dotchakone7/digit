<?php

namespace App\Assistant\Drivers;

use App\Assistant\AssistantReply;
use App\Assistant\Contracts\AssistantDriver;
use App\Models\Order;
use App\Models\User;
use App\Payments\PaymentManager;
use App\Services\ShippingService;
use Illuminate\Support\Str;

/** Keyword-based answers built from real shop data. No external API. */
class RuleBasedAssistant implements AssistantDriver
{
    public function __construct(
        private readonly PaymentManager $payments,
        private readonly ShippingService $shipping,
    ) {}

    public function reply(string $message, ?User $user, array $history = []): AssistantReply
    {
        $text = Str::lower(Str::ascii($message));

        return match (true) {
            $this->mentions($text, ['commande', 'colis', 'suivi', 'ou est']) => $this->orderStatus($text, $user),
            $this->mentions($text, ['payer', 'paiement', 'mobile money', 'orange', 'mtn', 'moov', 'wave']) => $this->paymentMethods(),
            $this->mentions($text, ['livraison', 'delai', 'livrer', 'expedition']) => $this->deliveryInfo(),
            $this->mentions($text, ['retour', 'rembourse', 'echange']) => $this->returnPolicy(),
            $this->mentions($text, ['produit', 'catalogue', 'avez-vous', 'cherche', 'choisir', 'conseil']) => $this->catalog(),
            default => new AssistantReply(
                'Je peux vous aider à suivre une commande, connaître les moyens de paiement, les délais de livraison ou notre politique de retour. Que souhaitez-vous savoir ?'
            ),
        };
    }

    private function orderStatus(string $text, ?User $user): AssistantReply
    {
        if ($user === null) {
            return new AssistantReply('Connectez-vous pour que je puisse retrouver vos commandes.', [
                ['label' => 'Se connecter', 'url' => route('login')],
            ]);
        }

        $query = Order::query()->where('user_id', $user->id)->latest();

        if (preg_match('/[a-z]{2,5}-\d{6}-[a-z0-9]{5}/', $text, $match)) {
            $query->where('number', Str::upper($match[0]));
        }

        $order = $query->first();

        if ($order === null) {
            return new AssistantReply("Je n'ai trouvé aucune commande sur votre compte.");
        }

        return new AssistantReply(
            "Votre commande {$order->number} est actuellement : {$order->status->label()} (paiement : {$order->payment_status->label()}).",
            [['label' => 'Voir le détail', 'url' => route('account.orders.show', $order)]],
        );
    }

    private function paymentMethods(): AssistantReply
    {
        $labels = $this->payments->available()->map->label()->values()->implode(', ');

        return new AssistantReply("Vous pouvez payer par : {$labels}. Le paiement est toujours vérifié par nos équipes avant expédition.");
    }

    private function deliveryInfo(): AssistantReply
    {
        $lines = $this->shipping->methods()
            ->map(fn ($m) => $m->name.' ('.($m->estimated_delay ?: 'délai variable').', '.($m->price ? money($m->price) : 'gratuit').')')
            ->implode(' ; ');

        return new AssistantReply($lines ? "Nos options de livraison : {$lines}." : 'Les options de livraison sont présentées lors de la commande.', [
            ['label' => 'Informations de livraison', 'url' => route('pages.show', 'livraison')],
        ]);
    }

    private function returnPolicy(): AssistantReply
    {
        $days = config('shop.orders.return_window_days');

        return new AssistantReply("Vous disposez de {$days} jours après la livraison pour demander un retour depuis votre espace client.", [
            ['label' => 'Politique de remboursement', 'url' => route('pages.show', 'remboursement')],
        ]);
    }

    private function catalog(): AssistantReply
    {
        return new AssistantReply('Parcourez notre catalogue par catégorie ou utilisez la recherche en haut de page.', [
            ['label' => 'Voir le catalogue', 'url' => route('catalog.index')],
        ]);
    }

    private function mentions(string $text, array $keywords): bool
    {
        return Str::contains($text, $keywords);
    }
}
