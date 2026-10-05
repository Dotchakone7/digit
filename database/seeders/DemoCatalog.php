<?php

namespace Database\Seeders;

/** Realistic demo catalog (prices in FCFA). Images live in demo-images/. */
final class DemoCatalog
{
    public static function categories(): array
    {
        return [
            ['slug' => 'electronique', 'name' => 'Électronique', 'image' => 'casque-audio-sans-fil', 'description' => 'Audio, smartphones et accessoires high-tech sélectionnés pour leur fiabilité.',
                'children' => [['slug' => 'audio', 'name' => 'Audio'], ['slug' => 'smartphones-accessoires', 'name' => 'Smartphones & accessoires']]],
            ['slug' => 'mode', 'name' => 'Mode', 'image' => 'robe-wax', 'description' => 'Vêtements et chaussures alliant style contemporain et savoir-faire local.',
                'children' => [['slug' => 'vetements', 'name' => 'Vêtements'], ['slug' => 'chaussures', 'name' => 'Chaussures']]],
            ['slug' => 'accessoires', 'name' => 'Accessoires', 'image' => 'sac-a-main-cuir', 'description' => 'Sacs, montres et lunettes pour compléter chaque tenue.'],
            ['slug' => 'maison-deco', 'name' => 'Maison & déco', 'image' => 'lampe-design', 'description' => 'Des objets choisis pour un intérieur chaleureux.'],
            ['slug' => 'beaute-bien-etre', 'name' => 'Beauté & bien-être', 'image' => 'parfum', 'description' => 'Soins naturels, parfums et rituels bien-être.'],
            ['slug' => 'sport-loisirs', 'name' => 'Sport & loisirs', 'image' => 'tapis-yoga', 'description' => 'Équipement pour bouger, s’entraîner et se ressourcer.'],
        ];
    }

    public static function products(): array
    {
        return [
            ['audio', 'Casque audio sans fil Studio Pro', 89000, 74900, ['casque-audio-sans-fil', 'casque-audio-sans-fil-2'], 24, true,
                'Réduction de bruit active, 40 h d’autonomie et un son ample pour vos journées.',
                ['Autonomie' => '40 heures', 'Connectivité' => 'Bluetooth 5.3', 'Réduction de bruit' => 'Active (ANC)', 'Poids' => '250 g', 'Garantie' => '12 mois']],
            ['audio', 'Écouteurs Bluetooth AirBeat', 35000, null, ['ecouteurs-bluetooth'], 48, false,
                'Des écouteurs compacts, un boîtier de charge de poche et un son clair pour les appels.',
                ['Autonomie' => '6 h (+24 h avec boîtier)', 'Étanchéité' => 'IPX4', 'Connectivité' => 'Bluetooth 5.2', 'Garantie' => '12 mois']],
            ['audio', 'Enceinte portable Boom 360', 49000, 42000, ['enceinte-portable', 'enceinte-portable-2'], 15, true,
                'Un son à 360° puissant et une résistance à l’eau pour vos sorties à la plage.',
                ['Puissance' => '20 W', 'Autonomie' => '15 heures', 'Étanchéité' => 'IP67', 'Garantie' => '12 mois']],
            ['smartphones-accessoires', 'Smartphone Nova X 128 Go', 189000, null, ['smartphone', 'smartphone-2'], 9, true,
                'Grand écran 6,7", triple capteur photo et batterie longue durée pour toute la journée.',
                ['Écran' => '6,7" AMOLED', 'Stockage' => '128 Go', 'Mémoire' => '8 Go', 'Batterie' => '5000 mAh', 'Double SIM' => 'Oui']],
            ['smartphones-accessoires', 'Batterie externe PowerMax 20 000 mAh', 18500, 15500, ['batterie-externe'], 60, false,
                'Rechargez votre téléphone jusqu’à 4 fois, avec charge rapide 22,5 W.',
                ['Capacité' => '20 000 mAh', 'Ports' => 'USB-C + 2 USB-A', 'Charge rapide' => '22,5 W']],
            ['accessoires', 'Montre connectée Pulse 2', 65000, null, ['montre-connectee'], 4, false,
                'Suivi d’activité, sommeil et notifications, avec une autonomie de 10 jours.',
                ['Écran' => '1,4" tactile', 'Autonomie' => '10 jours', 'Étanchéité' => '5 ATM', 'Compatibilité' => 'Android & iOS']],
            ['accessoires', 'Montre classique Heritage', 72000, 59000, ['montre-classique'], 12, false,
                'Cadran épuré, bracelet en cuir véritable et mouvement à quartz japonais.',
                ['Boîtier' => 'Acier inoxydable 40 mm', 'Bracelet' => 'Cuir véritable', 'Mouvement' => 'Quartz japonais', 'Étanchéité' => '3 ATM']],
            ['accessoires', 'Sac à main en cuir Abidjan', 85000, null, ['sac-a-main-cuir', 'sac-a-main-cuir-2'], 7, true,
                'Cuir pleine fleur travaillé à la main, compartiments intérieurs et fermoir doré.',
                ['Matière' => 'Cuir pleine fleur', 'Dimensions' => '32 × 24 × 12 cm', 'Fabrication' => 'Artisanale']],
            ['accessoires', 'Sac à dos urbain Metro', 39000, 33000, ['sac-a-dos', 'sac-a-dos-2'], 22, false,
                'Compartiment ordinateur 15", tissu déperlant et dos ventilé pour vos trajets quotidiens.',
                ['Volume' => '22 L', 'Ordinateur' => 'Jusqu’à 15,6"', 'Tissu' => 'Polyester déperlant']],
            ['accessoires', 'Lunettes de soleil Lagune', 25000, null, ['lunettes-de-soleil'], 30, false,
                'Verres polarisés UV400 et monture légère pour un confort toute la journée.',
                ['Protection' => 'UV400, polarisés', 'Monture' => 'Acétate', 'Étui' => 'Inclus']],
            ['accessoires', 'Casquette brodée Signature', 12000, null, ['casquette'], 40, false,
                'Coton épais, broderie ton sur ton et réglage par boucle métallique.',
                ['Matière' => '100 % coton', 'Taille' => 'Réglable']],
            ['chaussures', 'Baskets urbaines Cloud', 55000, null, ['baskets-urbaines', 'baskets-urbaines-2'], 0, true,
                'Semelle amortissante et tige respirante : la basket du quotidien, confortable du matin au soir.',
                ['Tige' => 'Mesh respirant', 'Semelle' => 'Mousse amortissante', 'Entretien' => 'Lavable à 30 °C'],
                ['Pointure' => ['39' => 4, '40' => 6, '41' => 8, '42' => 5, '43' => 3, '44' => 0]]],
            ['chaussures', 'Baskets running Sprint', 62000, 52000, ['baskets-running'], 0, false,
                'Légères et réactives, idéales pour la course sur route et l’entraînement.',
                ['Poids' => '260 g', 'Drop' => '8 mm', 'Usage' => 'Route, fitness'],
                ['Pointure' => ['40' => 3, '41' => 5, '42' => 6, '43' => 2]]],
            ['vetements', 'T-shirt coton bio Essentiel', 9500, null, ['tshirt-coton-bio', 'tshirt-coton-bio-2'], 0, false,
                'Coton biologique 180 g/m², coupe droite et finitions soignées.',
                ['Matière' => '100 % coton biologique', 'Grammage' => '180 g/m²', 'Coupe' => 'Droite'],
                ['Taille' => ['S' => 12, 'M' => 20, 'L' => 18, 'XL' => 9]]],
            ['vetements', 'Polo en wax Héritage', 18000, 14500, ['polo-wax'], 0, true,
                'Un polo élégant aux motifs wax, confectionné par des tailleurs locaux.',
                ['Matière' => 'Coton wax', 'Confection' => 'Artisanale, Abidjan'],
                ['Taille' => ['M' => 6, 'L' => 8, 'XL' => 4]]],
            ['vetements', 'Robe longue en wax Akwaba', 32000, null, ['robe-wax'], 0, true,
                'Robe fluide aux imprimés vibrants, parfaite pour les cérémonies comme pour le quotidien.',
                ['Matière' => 'Coton wax', 'Longueur' => 'Longue', 'Entretien' => 'Lavage à la main'],
                ['Taille' => ['S' => 3, 'M' => 5, 'L' => 4]]],
            ['vetements', 'Robe d’été Brise', 24000, 19900, ['robe-ete'], 0, false,
                'Coupe évasée et tissu léger pour rester élégante sous la chaleur.',
                ['Matière' => 'Viscose', 'Doublure' => 'Non'],
                ['Taille' => ['S' => 4, 'M' => 6, 'L' => 2]]],
            ['maison-deco', 'Lampe à poser Halo', 38000, null, ['lampe-design'], 14, false,
                'Une lumière douce et chaleureuse, abat-jour en lin et pied en métal brossé.',
                ['Abat-jour' => 'Lin naturel', 'Hauteur' => '45 cm', 'Ampoule' => 'E27 (non incluse)']],
            ['maison-deco', 'Mug en céramique Artisan', 7500, null, ['mug-ceramique'], 55, false,
                'Céramique émaillée à la main, chaque pièce est unique.',
                ['Contenance' => '350 ml', 'Matière' => 'Céramique', 'Lave-vaisselle' => 'Oui']],
            ['maison-deco', 'Plante d’intérieur & cache-pot', 15000, null, ['plante-interieur'], 18, false,
                'Une plante facile d’entretien livrée dans un cache-pot en céramique mate.',
                ['Hauteur' => '40 à 50 cm', 'Entretien' => 'Facile', 'Exposition' => 'Lumière indirecte']],
            ['maison-deco', 'Bougie parfumée Vanille & Coco', 11000, 8900, ['bougie-parfumee'], 35, false,
                'Cire végétale et parfum gourmand pour 45 heures de combustion.',
                ['Cire' => 'Végétale (soja)', 'Durée' => '45 heures', 'Poids' => '220 g']],
            ['maison-deco', 'Coussin décoratif Bogolan', 14000, null, ['coussin-decoratif', 'coussin-decoratif-2'], 26, false,
                'Housse en coton aux motifs inspirés du bogolan, garnissage moelleux inclus.',
                ['Dimensions' => '45 × 45 cm', 'Housse' => 'Coton, déhoussable']],
            ['beaute-bien-etre', 'Eau de parfum Soleil d’Assinie', 42000, null, ['parfum', 'parfum-2'], 16, true,
                'Notes de fleur d’oranger, ambre et bois de santal pour un sillage lumineux.',
                ['Contenance' => '50 ml', 'Famille' => 'Florale ambrée', 'Tenue' => '8 heures']],
            ['beaute-bien-etre', 'Crème au beurre de karité pur', 6500, 5500, ['creme-karite'], 3, false,
                'Karité non raffiné, issu du commerce équitable, pour le corps et les cheveux.',
                ['Contenance' => '200 ml', 'Ingrédients' => '100 % beurre de karité', 'Origine' => 'Afrique de l’Ouest']],
            ['sport-loisirs', 'Tapis de yoga antidérapant Zen', 22000, null, ['tapis-yoga'], 20, false,
                'Épaisseur 6 mm, surface antidérapante et sangle de transport incluse.',
                ['Épaisseur' => '6 mm', 'Dimensions' => '183 × 61 cm', 'Matière' => 'TPE sans PVC']],
            ['sport-loisirs', 'Haltères réglables 2 × 10 kg', 45000, 39000, ['halteres'], 8, false,
                'Disques interchangeables pour adapter la charge à chaque exercice.',
                ['Charge' => '2 × 10 kg', 'Disques' => 'Fonte revêtue', 'Barres' => 'Antidérapantes']],
            ['sport-loisirs', 'Gourde isotherme Fresh 750 ml', 13500, null, ['gourde-isotherme', 'gourde-isotherme-2'], 45, false,
                'Garde vos boissons fraîches 24 h et chaudes 12 h, sans BPA.',
                ['Contenance' => '750 ml', 'Isolation' => 'Double paroi inox', 'Froid' => '24 h']],
        ];
    }

    public static function reviewComments(): array
    {
        return [
            [5, 'Excellent achat', 'Produit conforme à la description, très bonne qualité. Livraison rapide à Cocody, le livreur était très aimable.'],
            [5, 'Je recommande', 'Deuxième commande sur la boutique et toujours aussi satisfaite. Le paiement par Mobile Money est très pratique.'],
            [4, 'Très bien', 'Bon rapport qualité-prix. Petit délai sur la livraison mais j’ai été prévenue à chaque étape.'],
            [5, 'Parfait', 'Emballage soigné, produit magnifique. Exactement comme sur les photos.'],
            [4, 'Satisfait', 'Conforme à mes attentes, je recommanderai sans hésiter.'],
            [3, 'Correct', 'Produit correct pour le prix, mais la couleur est légèrement différente de la photo.'],
            [5, 'Service au top', 'Le service client a répondu à toutes mes questions avant l’achat. Merci !'],
        ];
    }
}
