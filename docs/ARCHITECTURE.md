# Architecture

Ce document explique comment le code est organisé et comment l'étendre sans le fragiliser.

## Principes

- **Contrôleurs fins** : validation (Form Requests), autorisation (Policies/Gates), puis délégation à un service. Aucune règle métier dans les vues Blade ni dans les routes.
- **Règles métier dans `app/Services`** : un changement d'état important (commande, paiement, stock) passe toujours par le même service, qui écrit l'historique et émet les événements.
- **Montants entiers** (unité mineure de la devise) : `App\Support\Money`, helper `money()` pour l'affichage.
- **Le serveur fait foi** : prix, remises, frais de port et totaux sont recalculés côté serveur à chaque étape ; le front n'affiche que des estimations.
- **Contrats + gestionnaires** pour tout ce qui dépend d'un prestataire externe (paiement, livreur, assistant IA), configurés dans `config/*.php` et `.env`.

## Arborescence

```
app/
├── Assistant/            Contrat AssistantDriver, gestionnaire, driver à règles (sans API externe)
├── Console/Commands/     shop:create-admin
├── Delivery/             Contrat CourierProvider, CourierManager, driver « link » (URL/téléphone/WhatsApp)
├── Enums/                Statuts (commande, paiement, avis, retour, expédition), rôles, types de coupon
├── Events/ Listeners/    OrderPlaced, PaymentConfirmed, OrderStatusChanged → notifications ; fusion du panier à la connexion
├── Exceptions/           BusinessException (message destiné à l'utilisateur, rendu en toast / JSON 422)
├── Livewire/             Composants dynamiques : Shop/Catalog, Admin/{ProductTable, OrderTable, OrderManager, PaymentTable, ReviewModeration, UserTable, Dashboard}
├── Http/
│   ├── Controllers/Shop      Vitrine, panier, checkout, paiements, webhooks
│   ├── Controllers/Account   Espace client
│   ├── Controllers/Admin     Back-office
│   ├── Controllers/Auth      Connexion, inscription, mot de passe
│   ├── Middleware/           EnsureUserIsActive, SecurityHeaders
│   └── Requests/             Form Requests
├── Models/               Eloquent (relations, casts, scopes, protection mass-assignment via #[Fillable])
├── Notifications/        OrderNotification (base) et notifications client/admin
├── Payments/             Contrat PaymentGateway, PaymentManager, gateways, DTO, exceptions
├── Policies/             Order, Address, Product, Category, Review, User, ReturnRequest
├── Services/
│   ├── Cart/CartService           Panier invité/connecté, quantités, stock, coupons, fusion
│   ├── Catalog/ProductSearch      Filtres, tri, recherche tolérante, suggestions
│   ├── Catalog/ProductManager     Écriture produit : variantes, images, stock
│   ├── Orders/OrderService        Panier → commande (transaction + verrous de ligne)
│   ├── OrderStatusService         Workflow des statuts, effets de bord, historique
│   ├── PaymentService             Cycle de vie des paiements
│   ├── CouponService, ShippingService, ImageService, SettingsService, WishlistService
└── Support/              Money, Media
```

## Interface dynamique (Livewire)

Les pages restent rendues par des contrôleurs classiques (SEO, autorisation de route) ; les zones interactives sont des composants Livewire qui appellent **les mêmes services** que les contrôleurs.

- **Autorisation à chaque requête** : le trait `AuthorizesAbility` revérifie l'ability du composant (`orders.manage`, `catalog.manage`…) à l'affichage ET à chaque action ; les actions sensibles ajoutent `Gate::authorize()` (ex. confirmation de paiement = `payments.manage`). Les identifiants manipulés par l'interface sont relus en base et les valeurs d'énumération validées (`tryFrom`).
- **État dans l'URL** : `#[Url]` sur les filtres (liens partageables, bouton retour), `#[Locked]` sur ce que le navigateur ne doit pas modifier.
- **Temps réel sans WebSocket** : `wire:poll.<n>s.visible` (commandes 20 s, fiche commande 30 s, tableau de bord 60 s), uniquement quand l'onglet est visible ; un toast annonce les nouvelles commandes.
- **Navigation** : `wire:navigate` sur les liens internes ; un seul bundle JS (`resources/js/livewire.js`) démarre Livewire et l'instance Alpine partagée (stores panier, toasts, confirmation).
- Les routes POST historiques (statut, paiement, expédition) sont conservées pour les intégrations et les tests.

## Modèle de données (résumé)

```
roles 1─n users 1─n orders 1─n order_items
                │        ├─n payments
                │        ├─n shipments
                │        ├─n order_status_histories
                │        └─n return_requests
                ├─n addresses
                ├─n reviews n─1 products
                ├─1 carts 1─n cart_items n─1 products / product_variants
                └─n wishlist_items n─1 products
categories (parent_id → 2 niveaux) 1─n products 1─n product_images
                                             └─n product_variants
coupons 1─n coupon_usages n─1 orders
shipping_methods 1─n orders
settings (clé/valeur éditables), newsletter_subscribers, notifications
```

Choix notables :
- `wishlists` + `wishlist_items` de la spécification sont fusionnés en une table `wishlist_items` (une liste par utilisateur suffit).
- Les produits sont supprimés « en douceur » (soft delete) : l'historique des commandes reste intact.
- Une commande stocke un instantané (prix unitaire, nom, SKU, image, adresse JSON) : modifier un produit n'altère jamais une commande passée.
- Avec variantes, le stock du produit est la somme des stocks des variantes actives.

## Flux de commande

1. `CartService` valide chaque ajout (produit publié, variante appartenant au produit, stock, maximum par ligne).
2. `OrderService::placeOrder()` ouvre une transaction, **verrouille** (`SELECT … FOR UPDATE`) produits, variantes et coupon dans un ordre déterministe, relit les prix, revérifie stock et coupon, crée la commande et ses lignes, décrémente le stock, enregistre l'utilisation du coupon et l'historique, vide le panier. Deux clients ne peuvent donc pas acheter la même dernière unité.
3. `PaymentService::start()` crée une tentative de paiement (référence unique, expiration éventuelle) et demande au gateway de l'initier.
4. Le statut de la commande n'évolue ensuite que via `OrderStatusService::transition()`, qui applique la matrice `OrderStatus::allowedTransitions()` :

```
pending ──► confirmed ──► processing ──► shipped ──► delivered ──► refunded
   │            │              │
   └────────────┴──────────────┴──► cancelled   (remise en stock automatique)
```

## Paiements

```
Client ─► checkout ─► PaymentService::start ─► Gateway::initiate ─► (redirection prestataire)
Prestataire ─► POST /webhooks/paiements/{code} ─► Gateway::parseWebhook (signature)
             ─► PaymentService::handleWebhook (contrôle du montant) ─► markPaid (verrou, idempotent)
             ─► commande pending → confirmed ─► événement PaymentConfirmed ─► e-mail
Retour client ─► /paiements/{ref}/retour ─► PaymentService::refresh ─► Gateway::fetchStatus (serveur à serveur)
```

### Ajouter un prestataire

```php
namespace App\Payments\Gateways;

class ExempleGateway extends AbstractGateway
{
    public function isAvailable(): bool
    {
        return filled($this->config['api_key'] ?? null);
    }

    public function initiate(Payment $payment): PaymentInitiation
    {
        // Appel HTTP (Http::withToken(...)) à l'API d'initiation du prestataire,
        // avec notify_url = route('payments.webhook', $this->code())
        //      return_url = route('payments.return', $payment)
        return new PaymentInitiation(redirectUrl: $urlRenvoyeeParLePrestataire, providerReference: $idPrestataire);
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        // Vérifier la signature selon la documentation officielle, sinon :
        // throw new InvalidWebhookSignature(...)
    }

    public function fetchStatus(Payment $payment): ?PaymentStatus
    {
        // Appel à l'API de vérification du prestataire.
    }

    public function expiresAfterMinutes(): ?int
    {
        return (int) config('payments.expiration_minutes');
    }
}
```

Puis dans `config/payments.php` :

```php
'exemple' => [
    'driver' => App\Payments\Gateways\ExempleGateway::class,
    'label' => 'Orange Money',
    'description' => 'Paiement instantané depuis votre téléphone.',
    'api_key' => env('EXEMPLE_API_KEY'),
    'secret' => env('EXEMPLE_WEBHOOK_SECRET'),
],
```

et `PAYMENT_GATEWAYS=exemple,cash_on_delivery`. Écrire un test calqué sur `tests/Feature/Payments/PaymentFlowTest.php` (webhook signé, signature invalide, montant incohérent).

## Livraison

`CourierManager` résout le driver `COURIER_DRIVER` (`link` par défaut). Le driver `link` construit les options de contact (plateforme, WhatsApp avec message pré-rempli, téléphone) à partir des paramètres. Pour une intégration API (réservation automatique de course, suivi) : implémenter `CourierProvider` (`supportsApiBooking(): true` + méthodes de réservation), l'enregistrer dans `config/delivery.php` et créer/mettre à jour les `shipments` depuis le service.

## Autorisations (RBAC)

- `config/permissions.php` : abilities par rôle (`catalog.manage`, `orders.manage`, `payments.manage`, `users.manage`, `settings.manage`…). Un super administrateur a toutes les abilities (`Gate::before`).
- Les routes admin exigent `auth` + `can:admin.access`, puis l'ability de chaque section ; les Policies vérifient la propriété des ressources (commandes, adresses, avis) et les règles fines (un admin ne peut ni modifier un super admin ni se désactiver lui-même, un compte avec commandes ne se supprime pas).
- `EnsureUserIsActive` déconnecte immédiatement un compte désactivé ; ses sessions sont aussi supprimées côté base.

## Sécurité (récapitulatif)

Form Requests partout, Eloquent/Query Builder (requêtes paramétrées, échappement des `LIKE`), protection CSRF (sauf webhooks, authentifiés par signature), échappement Blade et JSON-LD `JSON_HEX_TAG`, liste blanche `#[Fillable]` (rôle, statuts et `is_active` jamais assignables en masse), uploads validés (MIME réel, taille, dimensions) puis **ré-encodés en WebP par GD** (supprime EXIF et contenu caché, nom aléatoire), limitation de débit (connexion, panier, checkout, formulaires, recherche, webhooks), en-têtes de sécurité, réponses identiques pour éviter l'énumération de comptes, URL du livreur limitée à http(s), journaux sans données sensibles (numéro payeur masqué), secrets uniquement dans `.env`, `serializable_classes` de Laravel 13 respecté (aucun objet en cache).

À envisager en production : une Content-Security-Policy (Livewire/Alpine nécessitent `unsafe-eval`, ou la build CSP de Livewire), un WAF/CDN, la vérification d'e-mail obligatoire si souhaitée (`MustVerifyEmail`).

## Notifications

`App\Notifications\OrderNotification` définit `toMail()`, `toArray()` (base de données) et `toText()` (texte court). Pour ajouter SMS ou WhatsApp :
1. créer un canal (`app/Notifications/Channels/SmsChannel.php`) qui appelle l'API du fournisseur avec `$notification->toText($notifiable)` ;
2. le référencer dans `via()` (ex. clé `sms` mappée sur la classe du canal) ;
3. ajouter `sms` à `SHOP_NOTIFICATION_CHANNELS`.

## Assistant / IA

L'assistant est désactivé par défaut (`ASSISTANT_ENABLED`). Le driver `rules` répond sans API externe à partir des vraies données (suivi de commande de l'utilisateur connecté, moyens de paiement actifs, modes de livraison, politique de retour). Pour un LLM (OpenAI, Anthropic Claude, modèle local…) : implémenter `App\Assistant\Contracts\AssistantDriver`, lire `config('assistant.providers')` (clé dans `.env`), l'enregistrer dans `config/assistant.php`. Les recommandations intelligentes peuvent réutiliser le même principe (contrat + driver) à côté de `ProductSearch`.

## Évolutions prévues par l'architecture

- **Application mobile** : les services sont indépendants des contrôleurs ; exposer une API (`routes/api.php` + Sanctum + API Resources) réutilisera `CartService`, `OrderService`, `PaymentService` sans duplication.
- **Marketplace** : ajouter un modèle `Vendor` lié aux produits et répartir les lignes de commande par vendeur dans `OrderService`.
- **Multi-devises / multi-langues** : la devise est centralisée dans `config/shop.php` et `Money` ; les textes d'interface sont en français et peuvent être extraits vers `lang/`.
