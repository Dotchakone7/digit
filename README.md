# Digit — Plateforme e-commerce

Boutique en ligne complète, pensée pour le marché ouest-africain (FCFA, Mobile Money, livraison à domicile) et prête à servir de base à une mise en production : vitrine premium, catalogue, panier, commande, paiements, espace client et back-office.

> Le site vitrine statique qui existait auparavant dans ce dépôt (agence K-One Digital) est conservé intact dans [`legacy/agency-site/`](legacy/agency-site/).

## Sommaire

1. [Fonctionnalités](#fonctionnalités)
2. [Stack technique](#stack-technique)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Base de données, migrations et seeders](#base-de-données-migrations-et-seeders)
6. [Créer un administrateur](#créer-un-administrateur)
7. [Paiements](#paiements)
8. [Livraison et bouton « Contacter un livreur »](#livraison-et-bouton--contacter-un-livreur-)
9. [E-mails et notifications](#e-mails-et-notifications)
10. [Identité visuelle](#identité-visuelle)
11. [Lancement local](#lancement-local)
12. [Tests et qualité](#tests-et-qualité)
13. [Déploiement](#déploiement)
14. [Architecture](#architecture)

## Fonctionnalités

**Vitrine** — page d'accueil (hero, catégories, populaires, promotions, nouveautés, avantages, témoignages réels, newsletter), catalogue (recherche tolérante aux accents et fautes de frappe, suggestions instantanées, filtres catégorie / prix / disponibilité / promotions, tri, grille ou liste, filtres en tiroir sur mobile), fiche produit (galerie avec zoom et plein écran, variantes avec stock, quantité, « Acheter maintenant », favoris, partage, avis vérifiés, produits similaires), mini-panier sans rechargement, panier avec codes promo, checkout en une page (coordonnées, adresse, livraison, paiement, récapitulatif permanent), pages légales, SEO (URLs propres, meta, Open Graph, JSON-LD Product/Breadcrumb/WebSite, `sitemap.xml`, `robots.txt`), pages d'erreur 403/404/419/429/500/503, assistant de discussion optionnel.

**Espace client** — tableau de bord, profil, sécurité (changement de mot de passe avec déconnexion des autres sessions), commandes avec suivi chronologique, annulation tant que non payée, paiement à finaliser, adresses, favoris persistants, avis, demandes de retour.

**Back-office** — tableau de bord (CA du mois et total, tendance, commandes en attente, clients, stock faible, graphique des ventes sur 30 jours accessible, meilleures ventes, activité récente), produits (variantes, caractéristiques, images optimisées, publication / dépublication / désactivation, suppression douce), catégories imbriquées, commandes (recherche, filtres, export CSV, workflow de statuts contrôlé, historique, expéditions, **Contacter un livreur**), paiements (vérification manuelle Mobile Money), modération des avis, codes promo, retours et remboursements, utilisateurs et rôles, modes de livraison, paramètres du site, newsletter.

**Rôles** — Super administrateur, Administrateur, Gestionnaire, Client (voir `config/permissions.php`).

## Stack technique

| Couche | Choix |
|---|---|
| Back-end | Laravel 13, PHP 8.3+ (8.4 recommandé en production) |
| Base de données | PostgreSQL 16 recommandé — MySQL 8 / MariaDB 10.6+ pris en charge (hébergement mutualisé) — SQLite pour les tests |
| Front-end | Blade, **Livewire 4** (catalogue, tableaux et fiches de l'administration en temps réel, navigation sans rechargement `wire:navigate`), Alpine.js (fourni par Livewire), Tailwind CSS 4, Vite 8 |
| Polices | Inter et Plus Jakarta Sans auto-hébergées (Fontsource, aucun appel externe) |
| Files d'attente | Laravel Queues (driver `database` par défaut) pour les notifications |

Un seul paquet Composer ajouté au squelette Laravel : `livewire/livewire`. Côté npm, seulement Tailwind et les polices (Alpine est inclus dans le bundle Livewire).

## Installation

Prérequis : PHP ≥ 8.3 avec `pdo_pgsql`, `gd` (avec WebP), `intl`, `mbstring` ; Composer 2 ; Node.js ≥ 20 ; PostgreSQL ≥ 14.

```bash
git clone <url-du-depot> digit && cd digit
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Créer la base PostgreSQL :

```sql
CREATE USER digit WITH PASSWORD 'un-mot-de-passe-fort';
CREATE DATABASE digit OWNER digit;
```

Renseigner `DB_PASSWORD` dans `.env`, puis :

```bash
php artisan migrate
php artisan db:seed                 # rôles + modes de livraison par défaut
php artisan storage:link            # images publiques
npm run build
```

## Configuration

Tout ce qui est variable ou sensible se configure dans `.env` (voir [`.env.example`](.env.example), entièrement commenté). Aucun secret n'est versionné.

| Domaine | Variables |
|---|---|
| Application | `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`, `APP_TIMEZONE`, `TRUSTED_PROXIES` |
| Base de données | `DB_*` |
| Sessions | `SESSION_ENCRYPT`, `SESSION_SECURE_COOKIE`, `SESSION_LIFETIME` |
| Stockage | `SHOP_MEDIA_DISK` (`public` ou `s3`), `SHOP_UPLOAD_MAX_KB`, `AWS_*` |
| Boutique | `SHOP_NAME`, `SHOP_TAGLINE`, `SHOP_LOGO`, `SHOP_FAVICON`, `SHOP_CURRENCY*`, `SHOP_ORDER_PREFIX`, `SHOP_LOW_STOCK_THRESHOLD`, `SHOP_RETURN_WINDOW_DAYS` |
| Contact & réseaux | `SHOP_CONTACT_*`, `SHOP_SOCIAL_*` |
| Paiement | `PAYMENT_GATEWAYS`, `PAYMENT_EXPIRATION_MINUTES`, `MOMO_*`, `PAYMENT_SANDBOX_SECRET` |
| Livraison | `COURIER_*` |
| E-mail | `MAIL_*`, `SHOP_ADMIN_NOTIFICATION_EMAIL`, `SHOP_NOTIFICATION_CHANNELS` |
| Assistant | `ASSISTANT_ENABLED`, `ASSISTANT_DRIVER`, `ASSISTANT_API_KEY`, `ASSISTANT_MODEL`, `ASSISTANT_BASE_URL` |

Les contenus (bandeau d'annonce, textes et image du hero, coordonnées, informations de livraison, livreur partenaire) sont aussi modifiables par le propriétaire dans **Admin › Paramètres** ; une valeur vide reprend celle du `.env`.

## Base de données, migrations et seeders

Tables principales : `roles`, `users`, `categories`, `products`, `product_images`, `product_variants`, `carts`, `cart_items`, `orders`, `order_items`, `order_status_histories`, `payments`, `shipments`, `shipping_methods`, `addresses`, `reviews`, `wishlist_items`, `coupons`, `coupon_usages`, `return_requests`, `newsletter_subscribers`, `settings`, `notifications`.

- Les montants sont des **entiers** dans l'unité mineure de la devise (le FCFA n'a pas de décimales) : aucun problème d'arrondi.
- Les commandes conservent une **copie figée** des prix, noms, références et adresse au moment de l'achat.
- Les rôles sont insérés par la migration (données de référence indispensables).

| Commande | Effet |
|---|---|
| `php artisan db:seed` | Rôles et modes de livraison par défaut (sans risque en production) |
| `php artisan db:seed --class=DemoSeeder` | Données de démonstration : 6 catégories, 27 produits illustrés, 12 clients, ~34 commandes passées par les vrais services (stocks, totaux, historique, paiements), avis, codes promo. **Refusé en production.** |
| `php artisan migrate:fresh --seed` | Réinitialisation complète (développement) |

Comptes de démonstration (mot de passe `password`) : `superadmin@example.com`, `admin@example.com`, `gestionnaire@example.com`, et des clients comme `aya.traore@example.com`.

Les illustrations produits de démonstration sont générées localement (`scripts/generate-demo-images.mjs`) : aucune image tierce soumise à droits. Remplacez-les par les vraies photos du client.

## Créer un administrateur

En production, ne chargez jamais les données de démo. Créez le premier compte de façon interactive :

```bash
php artisan shop:create-admin                 # super administrateur
php artisan shop:create-admin --role=admin    # ou admin / manager
```

Le mot de passe demandé doit faire 12 caractères minimum (majuscules, minuscules, chiffres). Les rôles se gèrent ensuite dans **Admin › Utilisateurs** ; seul un super administrateur peut nommer un autre super administrateur.

## Paiements

L'architecture de paiement est extensible : chaque moyen est un *gateway* implémentant `App\Payments\Contracts\PaymentGateway`, déclaré dans `config/payments.php` et activé via `PAYMENT_GATEWAYS`.

**Règle d'or appliquée partout :** un paiement n'est jamais considéré comme réussi parce que le client revient sur une page. Il ne passe à « payé » que par un webhook dont la signature est vérifiée, une interrogation serveur-à-serveur du prestataire, ou une confirmation manuelle d'un membre de l'équipe. La page de retour ne fait qu'**interroger** le serveur.

Statuts : `pending`, `processing`, `paid`, `failed`, `cancelled`, `expired`, `refunded`. Chaque paiement possède une référence unique ; la confirmation est idempotente et vérifie le montant ; un paiement en ligne non finalisé expire (`PAYMENT_EXPIRATION_MINUTES`) et la commande est annulée avec remise en stock (`payments:expire`, planifié toutes les 5 minutes).

| Gateway | Usage |
|---|---|
| `cash_on_delivery` | Paiement à la livraison ; l'équipe confirme l'encaissement. |
| `manual_mobile_money` | Le client transfère sur le numéro marchand (Orange Money, MTN MoMo, Moov Money, Wave — `MOMO_*`), puis saisit la référence de transaction ; l'équipe vérifie sur le relevé opérateur et confirme dans **Admin › Paiements**. Seuls les 4 derniers chiffres du numéro payeur sont conservés. |
| `sandbox` | Simulateur local d'un prestataire en ligne (page hébergée + webhook signé HMAC). Désactivé automatiquement en production. Nécessite `PAYMENT_SANDBOX_SECRET`. |

**Brancher un prestataire réel** (CinetPay, PayDunya, FedaPay, Wave Business, API Orange Money…) : le choix du prestataire et l'obtention des identifiants marchands reviennent au client ; aucune API n'a été « inventée » ici. Une fois le prestataire choisi : créer `app/Payments/Gateways/<Nom>Gateway.php` (initiation → URL de paiement, `parseWebhook()` avec vérification de signature, `fetchStatus()` via l'API de vérification), le déclarer dans `config/payments.php` avec ses clés lues depuis `.env`, l'ajouter à `PAYMENT_GATEWAYS`, et configurer chez le prestataire l'URL de notification `https://votre-domaine/webhooks/paiements/<code>`. Le reste (commandes, statuts, e-mails, expiration, admin) fonctionne sans modification. Détails dans [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## Livraison et bouton « Contacter un livreur »

- **Modes de livraison** (tarif, gratuité à partir d'un montant, délai) : **Admin › Livraisons**.
- **Livreur partenaire** : **Admin › Paramètres › Livraison** (ou `COURIER_*` dans `.env`) — URL de la plateforme, téléphone, WhatsApp, informations complémentaires. Aucune plateforme fictive n'est codée en dur.
- Sur chaque commande, le bouton **« Contacter un livreur »** ouvre directement la plateforme configurée ; des raccourcis WhatsApp (message pré-rempli : n° de commande, client, adresse, montant à encaisser) et appel sont proposés. S'il n'y a rien de configuré, le bouton mène au paramétrage.
- Intégration API future : implémenter `App\Delivery\Contracts\CourierProvider` et l'enregistrer dans `config/delivery.php`.

## E-mails et notifications

Notifications envoyées au client : commande reçue, paiement confirmé, changement de statut (confirmée, en préparation, expédiée, livrée, annulée, remboursée). Option : copie de chaque nouvelle commande à `SHOP_ADMIN_NOTIFICATION_EMAIL`. Canaux : `SHOP_NOTIFICATION_CHANNELS` (`mail,database` par défaut) ; SMS/WhatsApp/push s'ajoutent via un canal de notification Laravel (voir l'architecture).

Configurer un SMTP réel :

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.votre-fournisseur.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="boutique@votre-domaine.com"
```

Les notifications sont mises en file d'attente : lancez un worker (`php artisan queue:work`).

## Identité visuelle

Couleurs (principale, accent, succès, erreur, avertissement, info, graphique) et typographies sont définies **uniquement** dans [`resources/css/theme.css`](resources/css/theme.css) ; toutes les vues utilisent ces jetons. Logo, logo clair et favicon : `SHOP_LOGO`, `SHOP_LOGO_DARK`, `SHOP_FAVICON` (chemins relatifs à `public/`), sans logo un logo typographique est généré. Après modification : `npm run build`.

La vitrine (commerciale, aérée) et l'administration (dense, orientée productivité, barre latérale sombre) partagent ces jetons mais ont chacune leur feuille (`app.css`, `admin.css`).

## Lancement local

```bash
composer run dev          # serveur, file d'attente, logs et Vite en parallèle
```

ou séparément : `php artisan serve`, `php artisan queue:work`, `npm run dev`. Ouvrir http://localhost:8000 — back-office : http://localhost:8000/admin.

## Tests et qualité

```bash
php artisan test                                              # SQLite en mémoire (rapide)
DB_CONNECTION=pgsql DB_DATABASE=digit_test php artisan test   # moteur de production
vendor/bin/pint                                               # style de code
```

La suite couvre notamment : inscription / connexion / limitation de tentatives / comptes désactivés, accès admin et RBAC, création / modification / suppression de produits et validation des images, panier (stock, variantes, propriété des lignes, codes promo, fusion à la connexion), commande (totaux, prix figés, livraison gratuite, stock concurrent, annulation et remise en stock), paiements (webhook signé, signature invalide, montant incohérent, idempotence, page de retour non fiable, expiration, Mobile Money manuel), workflow des statuts et notifications, bouton livreur, avis vérifiés et modérés, recherche, favoris.

La CI GitHub Actions ([`.github/workflows/ci.yml`](.github/workflows/ci.yml)) exécute Pint, le build Vite et les tests sur PostgreSQL 16.

## Déploiement

> **Procédure complète pas à pas** (VPS Ubuntu, Nginx, HTTPS, worker, sauvegardes, mises à jour, plusieurs clients, hébergement cPanel) : [`docs/DEPLOIEMENT.md`](docs/DEPLOIEMENT.md) — version PDF : `docs/DEPLOIEMENT.pdf`. Fichiers prêts à l'emploi dans [`deploy/`](deploy/).
>
> **Guide d'utilisation à remettre au client** (non technique, avec captures) : [`docs/GUIDE-UTILISATEUR.md`](docs/GUIDE-UTILISATEUR.md) — version PDF : `docs/GUIDE-UTILISATEUR.pdf`.

Résumé :

1. Serveur : PHP 8.4-FPM + Nginx (ou Laravel Forge / Ploi / un PaaS), PostgreSQL, HTTPS obligatoire.
2. `.env` de production : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `TRUSTED_PROXIES` si derrière un proxy, vrais identifiants SMTP et paiement, `PAYMENT_GATEWAYS` sans `sandbox`.
3. Déploiement :
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan migrate --force
   php artisan db:seed --force          # rôles + livraison par défaut (idempotent)
   php artisan storage:link
   php artisan optimize                 # cache config, routes, vues, événements
   ```
4. Processus permanents : `php artisan queue:work --tries=3` (Supervisor/systemd) et le planificateur `* * * * * php /chemin/artisan schedule:run`.
5. Premier compte : `php artisan shop:create-admin`.
6. Sauvegardes quotidiennes de la base et du disque de médias.

## Architecture

Vue d'ensemble, choix techniques et guides d'extension (paiement, livraison, notifications SMS/WhatsApp, assistant IA, application mobile, marketplace) : [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

Pour comprendre la structure du code et apprendre à modifier, ajouter ou supprimer une fonctionnalité (recettes pas à pas) : [`docs/GUIDE-DEVELOPPEUR.md`](docs/GUIDE-DEVELOPPEUR.md).
