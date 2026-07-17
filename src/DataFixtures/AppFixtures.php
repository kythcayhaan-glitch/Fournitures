<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\DemandeMateriel;
use App\Entity\Article;
use App\Entity\LigneDemande;
use App\Entity\MouvementStock;
use App\Entity\User;
use App\Enum\TypeMouvement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ── Utilisateurs ────────────────────────────────────────────
        $admin = $this->createUser($manager, 'admin@example.com', 'Admin1234!', 'Alice', 'Admin', ['ROLE_ADMIN']);
        $manager1 = $this->createUser($manager, 'manager1@example.com', 'Manager1234!', 'Bob', 'Martin', ['ROLE_MANAGER']);
        $manager2 = $this->createUser($manager, 'manager2@example.com', 'Manager1234!', 'Claire', 'Dupont', ['ROLE_MANAGER']);

        $users = [];
        $userData = [
            ['david.leroi@example.com', 'User1234!', 'David', 'Leroi'],
            ['emma.blanc@example.com', 'User1234!', 'Emma', 'Blanc'],
            ['felix.moreau@example.com', 'User1234!', 'Félix', 'Moreau'],
            ['gaelle.simon@example.com', 'User1234!', 'Gaëlle', 'Simon'],
            ['hugo.thomas@example.com', 'User1234!', 'Hugo', 'Thomas'],
        ];
        foreach ($userData as [$email, $pass, $first, $last]) {
            $users[] = $this->createUser($manager, $email, $pass, $first, $last, []);
        }

        $manager->flush();

        // ── Catégories ──────────────────────────────────────────────
        $catData = [
            ['Papeterie',    'Stylos, papier, cahiers, enveloppes, classeurs'],
            ['Informatique', 'Souris, claviers, câbles, cartouches, clés USB'],
            ['Mobilier',     'Chaises, bureaux, étagères, accessoires de bureau'],
            ['Hygiène',      'Produits nettoyants, savons, essuie-mains, désinfectants'],
        ];
        $categories = [];
        foreach ($catData as [$name, $desc]) {
            $cat = new Category();
            $cat->setName($name)->setDescription($desc);
            $manager->persist($cat);
            $categories[$name] = $cat;
        }
        $manager->flush();

        // ── Articles ─────────────────────────────────────────────
        $articlesData = [
            // Papeterie
            ['Ramette papier A4 80g', 'PAP-A4-80G', 'Papier blanc 80g/m², 500 feuilles', 4.99, 'ramette', 50, 10, 'Papeterie'],
            ['Stylo bille bleu BIC', 'STY-BIC-BL', 'Stylo bille pointe moyenne', 0.80, 'unité', 200, 30, 'Papeterie'],
            ['Classeur A4 dos 8cm', 'CLA-A4-8',  'Classeur 4 anneaux, dos 8cm', 3.50, 'unité', 40, 8, 'Papeterie'],
            ['Post-it 75x75mm jaune', 'PST-7575-J', 'Bloc de 100 feuilles repositionnables', 2.99, 'paquet', 60, 15, 'Papeterie'],
            ['Enveloppe C4 kraft', 'ENV-C4-K', 'Enveloppe kraft 90g, format A4', 12.50, 'boîte', 8, 3, 'Papeterie'],

            // Informatique
            ['Cartouche encre noire HP 302', 'CAR-HP302-N', 'Cartouche jet d\'encre noire HP 302', 15.90, 'unité', 15, 5, 'Informatique'],
            ['Clé USB 16Go Kingston', 'USB-KNG-16G', 'Clé USB 3.0 16Go', 8.99, 'unité', 25, 5, 'Informatique'],
            ['Souris optique sans fil', 'SOU-OPT-WL', 'Souris 1600 DPI, USB nano-récepteur', 22.90, 'unité', 10, 3, 'Informatique'],
            ['Câble HDMI 2m', 'CBL-HDMI-2M', 'Câble HDMI 2.0 haute vitesse', 9.90, 'unité', 12, 4, 'Informatique'],
            ['Tapis de souris', 'TAP-SOU-STD', 'Tapis 250x210mm, surface tissu', 4.50, 'unité', 20, 5, 'Informatique'],

            // Mobilier
            ['Chaise de bureau ergonomique', 'CHR-ERG-01', 'Chaise réglable, accoudoirs, roulettes', 189.00, 'unité', 5, 2, 'Mobilier'],
            ['Organiseur de bureau', 'ORG-BUR-01', 'Organiseur 5 compartiments, noir', 14.90, 'unité', 15, 3, 'Mobilier'],
            ['Lampe de bureau LED', 'LAM-LED-01', 'Lampe LED 12W, col flexible', 29.90, 'unité', 8, 2, 'Mobilier'],
            ['Porte-documents A4', 'POR-DOC-A4', 'Porte-documents à levier, polypropylène', 2.20, 'unité', 30, 10, 'Mobilier'],

            // Hygiène
            ['Savon liquide mains 500ml', 'SAV-LIQ-500', 'Savon antibactérien, parfum neutre', 3.20, 'flacon', 30, 8, 'Hygiène'],
            ['Essuie-mains papier Z', 'ESS-PAP-Z', 'Colis de 3000 feuilles, pliage en Z', 18.50, 'carton', 6, 2, 'Hygiène'],
            ['Gel hydroalcoolique 500ml', 'GEL-HYD-500', 'Gel désinfectant mains 70% alcool', 5.90, 'flacon', 20, 5, 'Hygiène'],
            ['Sacs poubelles 100L (rouleau)', 'SAC-POB-100', 'Sacs noirs 100L, rouleau de 20', 6.50, 'rouleau', 15, 4, 'Hygiène'],
            ['Liquide vaisselle 1L', 'LIQ-VAI-1L', 'Liquide vaisselle dégraissant citron', 2.80, 'flacon', 12, 4, 'Hygiène'],
            ['Lingettes désinfectantes', 'LNG-DES-80', 'Boîte 80 lingettes multi-surfaces', 4.99, 'boîte', 3, 5, 'Hygiène'],
        ];

        $articles = [];
        foreach ($articlesData as [$name, $ref, $desc, $price, $unit, $stock, $min, $catName]) {
            $f = new Article();
            $f->setName($name)
              ->setReference($ref)
              ->setDescription($desc)
              ->setUnitPrice($price)
              ->setUnit($unit)
              ->setStockQuantity($stock)
              ->setStockMinimum($min)
              ->setCategory($categories[$catName]);
            $manager->persist($f);
            $articles[$ref] = $f;

            // Mouvement initial (entrée stock)
            $mouv = new MouvementStock();
            $mouv->setType(TypeMouvement::ENTREE)
                 ->setQuantite($stock)
                 ->setQuantiteAvant(0)
                 ->setQuantiteApres($stock)
                 ->setMotif('Stock initial')
                 ->setArticle($f)
                 ->setOperateur($admin);
            $manager->persist($mouv);
        }
        $manager->flush();

        // ── Demandes ────────────────────────────────────────────────
        $demandesData = [
            [
                'user' => $users[0],
                'motif' => 'Renouvellement articles bureau pôle RH — manque de papier et stylos.',
                'statut' => 'delivered',
                'lignes' => [
                    ['PAP-A4-80G', 10, 10],
                    ['STY-BIC-BL', 20, 20],
                    ['CLA-A4-8', 5, 5],
                ],
                'processedBy' => $manager1,
                'commentaire' => null,
            ],
            [
                'user' => $users[1],
                'motif' => 'Besoin urgent de cartouches d\'encre pour l\'imprimante de la salle de réunion.',
                'statut' => 'approved',
                'lignes' => [
                    ['CAR-HP302-N', 3, 0],
                    ['PAP-A4-80G', 5, 0],
                ],
                'processedBy' => $manager2,
                'commentaire' => 'Approuvé, livraison prévue sous 48h.',
            ],
            [
                'user' => $users[2],
                'motif' => 'Demande de matériel informatique pour les nouveaux collaborateurs.',
                'statut' => 'pending',
                'lignes' => [
                    ['SOU-OPT-WL', 2, 0],
                    ['USB-KNG-16G', 4, 0],
                    ['TAP-SOU-STD', 2, 0],
                ],
                'processedBy' => null,
                'commentaire' => null,
            ],
            [
                'user' => $users[3],
                'motif' => 'Réapprovisionnement produits hygiène pour les sanitaires du 2ème étage.',
                'statut' => 'rejected',
                'lignes' => [
                    ['SAV-LIQ-500', 5, 0],
                    ['ESS-PAP-Z', 2, 0],
                    ['GEL-HYD-500', 4, 0],
                ],
                'processedBy' => $manager1,
                'commentaire' => 'Une commande est déjà en cours pour ce département. Veuillez attendre la livraison.',
            ],
            [
                'user' => $users[4],
                'motif' => 'Post-its et organiseurs pour le pôle comptabilité.',
                'statut' => 'pending',
                'lignes' => [
                    ['PST-7575-J', 3, 0],
                    ['ORG-BUR-01', 2, 0],
                ],
                'processedBy' => null,
                'commentaire' => null,
            ],
            [
                'user' => $users[0],
                'motif' => 'Remplacement lampe de bureau cassée et câble HDMI pour salle de conférence.',
                'statut' => 'delivered',
                'lignes' => [
                    ['LAM-LED-01', 1, 1],
                    ['CBL-HDMI-2M', 2, 2],
                ],
                'processedBy' => $manager2,
                'commentaire' => null,
            ],
            [
                'user' => $users[1],
                'motif' => 'Besoin de sacs poubelles et lingettes pour la cuisine.',
                'statut' => 'pending',
                'lignes' => [
                    ['SAC-POB-100', 3, 0],
                    ['LNG-DES-80', 5, 0],
                    ['LIQ-VAI-1L', 2, 0],
                ],
                'processedBy' => null,
                'commentaire' => null,
            ],
            [
                'user' => $users[2],
                'motif' => 'Enveloppes et porte-documents pour envoi dossiers clients.',
                'statut' => 'approved',
                'lignes' => [
                    ['ENV-C4-K', 2, 0],
                    ['POR-DOC-A4', 10, 0],
                ],
                'processedBy' => $manager1,
                'commentaire' => 'Approuvé.',
            ],
            [
                'user' => $users[3],
                'motif' => 'Articles bureautiques pour accueil des stagiaires.',
                'statut' => 'delivered',
                'lignes' => [
                    ['STY-BIC-BL', 15, 15],
                    ['CLA-A4-8', 8, 8],
                    ['PAP-A4-80G', 3, 3],
                ],
                'processedBy' => $manager2,
                'commentaire' => null,
            ],
            [
                'user' => $users[4],
                'motif' => 'Renouvellement chaise de bureau — ancienne endommagée.',
                'statut' => 'pending',
                'lignes' => [
                    ['CHR-ERG-01', 1, 0],
                ],
                'processedBy' => null,
                'commentaire' => null,
            ],
        ];

        $dayOffset = 30;
        foreach ($demandesData as $i => $data) {
            $demande = new DemandeMateriel();
            $dayOffset -= 2;
            $requestedAt = new \DateTimeImmutable("-{$dayOffset} days");

            $demande->setReference(sprintf('DEM-%s-%04d', $requestedAt->format('Ymd'), $i + 1));
            $demande->setMotif($data['motif']);
            $demande->setStatut($data['statut']);
            $demande->setRequester($data['user']);

            // Accès à la propriété protégée via réflexion pour requestedAt
            $ref = new \ReflectionProperty(DemandeMateriel::class, 'requestedAt');
            $ref->setValue($demande, $requestedAt);

            if ($data['processedBy']) {
                $demande->setProcessedBy($data['processedBy']);
                $demande->setProcessedAt($requestedAt->modify('+1 day'));
            }
            if ($data['commentaire']) {
                $demande->setCommentaire($data['commentaire']);
            }

            foreach ($data['lignes'] as [$fourRef, $qtyDemandee, $qtyServie]) {
                $ligne = new LigneDemande();
                $ligne->setArticle($articles[$fourRef]);
                $ligne->setQuantiteDemandee($qtyDemandee);
                $ligne->setQuantiteServie($qtyServie);
                $demande->addLigne($ligne);
                $manager->persist($ligne);
            }

            $manager->persist($demande);
        }

        $manager->flush();
    }

    private function createUser(
        ObjectManager $manager,
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        array $roles
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $manager->persist($user);
        return $user;
    }
}
