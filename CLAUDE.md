# Digit — plateforme e-commerce Laravel

## Commandes utiles
- Tests : `php artisan test` (SQLite en mémoire) — en PostgreSQL : `DB_CONNECTION=pgsql DB_DATABASE=digit_test php artisan test`
- Style : `vendor/bin/pint` (doit passer avant tout commit, la CI exécute `pint --test`)
- Assets : `npm run build` / `npm run dev`
- Démo : `php artisan migrate:fresh --seed` puis `php artisan db:seed --class=DemoSeeder`

## Conventions
- Montants = entiers en unité mineure de la devise (`App\Support\Money`, helper `money()`), jamais de float.
- Logique métier dans `app/Services` (panier, commande, statuts, paiement) — contrôleurs fins, Form Requests pour la validation.
- Les statuts de commande/paiement ne changent QUE via `OrderStatusService` / `PaymentService` (historique + événements).
- Un paiement n'est « payé » que par webhook vérifié, API prestataire ou confirmation manuelle du staff.
- Autorisations : `config/permissions.php` (abilities par rôle) + Policies ; jamais de contrôle côté front uniquement.
- Couleurs/typo : uniquement via les tokens de `resources/css/theme.css`.
- Ne jamais mettre en cache des objets Eloquent (Laravel 13 refuse leur désérialisation) : cacher des tableaux.
- Voir `docs/ARCHITECTURE.md` pour ajouter un moyen de paiement, un livreur API, un canal de notification ou un moteur IA.
