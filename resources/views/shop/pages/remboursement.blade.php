@extends('shop.pages.layout')

@section('page')
    <p class="rounded-xl bg-warning-50 p-4 text-sm text-warning-700">Modèle de texte à faire valider par le propriétaire de la boutique (et un conseil juridique) avant la mise en production.</p>
    <h2>Délai de retour</h2>
    <p>Vous disposez de <strong>{{ config('shop.orders.return_window_days') }} jours</strong> à compter de la livraison pour demander le retour d’un article, directement depuis la page de détail de votre commande dans votre espace client.</p>
    <h2>Conditions</h2>
    <ul>
        <li>L’article doit être retourné dans son état d’origine, complet et dans son emballage.</li>
        <li>Les articles personnalisés ou d’hygiène descellés ne peuvent pas être repris.</li>
        <li>Les produits défectueux ou non conformes sont repris sans frais.</li>
    </ul>
    <h2>Traitement de la demande</h2>
    <p>Chaque demande est examinée par notre équipe : elle est acceptée ou refusée, puis le produit est récupéré et contrôlé. Vous suivez l’avancement (demandé, accepté, reçu, remboursé) depuis votre compte.</p>
    <h2>Remboursement</h2>
    <p>Après réception et contrôle du produit, le remboursement est effectué via le moyen de paiement initial ou par Mobile Money, sous un délai indicatif de 7 jours ouvrés.</p>
@endsection
