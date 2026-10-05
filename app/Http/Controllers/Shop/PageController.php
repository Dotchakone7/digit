<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PageController extends Controller
{
    /** Legal & information pages (templates in resources/views/shop/pages). */
    public const PAGES = [
        'livraison' => ['title' => 'Informations de livraison', 'description' => 'Zones, délais et frais de livraison.'],
        'remboursement' => ['title' => 'Retours & remboursements', 'description' => 'Conditions de retour et de remboursement.'],
        'conditions-generales' => ['title' => 'Conditions générales de vente', 'description' => 'Conditions générales de vente de la boutique.'],
        'confidentialite' => ['title' => 'Politique de confidentialité', 'description' => 'Comment nous protégeons vos données personnelles.'],
    ];

    public function show(string $page): View
    {
        return view('shop.pages.'.$page, ['page' => self::PAGES[$page] + ['slug' => $page]]);
    }

    public function contact(): View
    {
        return view('shop.contact');
    }
}
