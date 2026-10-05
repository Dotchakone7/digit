@extends('shop.pages.layout')

@section('page')
    <p class="rounded-xl bg-warning-50 p-4 text-sm text-warning-700">Modèle de conditions générales à compléter (raison sociale, RCCM, adresse, juridiction compétente…) et à faire valider juridiquement.</p>
    <h2>1. Objet</h2>
    <p>Les présentes conditions régissent les ventes conclues sur le site {{ config('shop.name') }} entre la boutique et tout client.</p>
    <h2>2. Produits et prix</h2>
    <p>Les prix sont indiqués en {{ config('shop.currency.symbol') }}, toutes taxes comprises, hors frais de livraison. Le prix appliqué est celui affiché au moment de la validation de la commande ; il est enregistré sur la commande et ne peut plus être modifié.</p>
    <h2>3. Commande</h2>
    <p>La commande est validée après confirmation du récapitulatif. Un e-mail de confirmation est envoyé. La boutique se réserve le droit d’annuler une commande en cas de rupture de stock ou de litige de paiement.</p>
    <h2>4. Paiement</h2>
    <p>Le paiement s’effectue par Mobile Money, à la livraison ou par tout autre moyen proposé lors de la commande. Une commande n’est considérée comme payée qu’après confirmation effective de la réception des fonds.</p>
    <h2>5. Livraison</h2>
    <p>Les délais et frais sont précisés sur la page <a class="link" href="{{ route('pages.show', 'livraison') }}">Informations de livraison</a>.</p>
    <h2>6. Retours</h2>
    <p>Voir notre <a class="link" href="{{ route('pages.show', 'remboursement') }}">politique de retours et remboursements</a>.</p>
    <h2>7. Données personnelles</h2>
    <p>Voir notre <a class="link" href="{{ route('pages.show', 'confidentialite') }}">politique de confidentialité</a>.</p>
@endsection
