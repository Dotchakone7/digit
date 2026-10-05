<?php

use App\Enums\RoleSlug;

/*
| Role → abilities map. Super admins implicitly have every ability
| (see AuthServiceProvider::boot / Gate::before). Abilities are checked
| server-side by Gates, Policies and the "can:" middleware.
*/
return [
    'abilities' => [
        'admin.access' => 'Accéder au back-office',
        'dashboard.view' => 'Voir les statistiques',
        'catalog.manage' => 'Gérer produits et catégories',
        'orders.manage' => 'Gérer les commandes et livraisons',
        'payments.manage' => 'Gérer les paiements',
        'reviews.moderate' => 'Modérer les avis',
        'coupons.manage' => 'Gérer les promotions',
        'returns.manage' => 'Gérer les retours',
        'customers.view' => 'Voir les clients',
        'users.manage' => 'Gérer les utilisateurs et rôles',
        'settings.manage' => 'Gérer les paramètres du site',
    ],

    'roles' => [
        RoleSlug::Admin->value => [
            'admin.access', 'dashboard.view', 'catalog.manage', 'orders.manage', 'payments.manage',
            'reviews.moderate', 'coupons.manage', 'returns.manage', 'customers.view', 'users.manage',
            'settings.manage',
        ],
        RoleSlug::Manager->value => [
            'admin.access', 'dashboard.view', 'catalog.manage', 'orders.manage', 'reviews.moderate',
            'returns.manage', 'customers.view',
        ],
        RoleSlug::Customer->value => [],
    ],
];
